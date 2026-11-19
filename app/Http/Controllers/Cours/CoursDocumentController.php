<?php

namespace App\Http\Controllers\Cours;

use App\Actions\ConvertirDocumentEnPdf;
use App\Actions\StoreUploadedFile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cours\StoreCoursDocumentRequest;
use App\Models\Cours;
use App\Models\CoursDocument;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class CoursDocumentController extends Controller
{
    /**
     * Uploade un document et l'associe au cours.
     *
     * La validation (dont le MIME réel, pas uniquement l'extension client) est assurée par StoreCoursDocumentRequest.
     * L'autorisation délègue à CoursPolicy::update().
     *
     * @throws AuthorizationException
     */
    public function store(StoreCoursDocumentRequest $request, Cours $cours, StoreUploadedFile $fichiers): RedirectResponse
    {
        $meta = $fichiers->execute(
            $request->file('document'),
            "images/cours/{$cours->id}"
        );

        $cours->documents()->create([
            'enseignant_id' => auth()->id(),
            'type' => strtolower($request->file('document')->getClientOriginalExtension()),
            ...$meta,
        ]);

        return back()->with('success', __('document.added'));
    }

    /**
     * Télécharge un document avec son nom original, sans exposer le nom UUID sur disque.
     *
     * @throws AuthorizationException
     */
    public function download(Cours $cours, CoursDocument $document): BinaryFileResponse
    {
        abort_if($document->cours_id !== $cours->id, 404);
        $this->authorize('viewDocuments', $cours);

        $path = public_path($document->file_path);
        abort_unless(is_file($path), 404);

        return response()->download($path, $document->nom_original);
    }

    /**
     * Affiche le document en PDF dans le navigateur, sans le télécharger.
     *
     * Les documents Word sont convertis au premier aperçu. Si la conversion échoue,
     * l'utilisateur reçoit le fichier original en téléchargement plutôt qu'une page d'erreur.
     *
     * @throws AuthorizationException
     */
    public function apercu(Cours $cours, CoursDocument $document,
        ConvertirDocumentEnPdf $convertirDocumentEnPdf): BinaryFileResponse|RedirectResponse
    {
        abort_if($document->cours_id !== $cours->id, 404);
        $this->authorize('viewDocuments', $cours);
        abort_unless(is_file(public_path($document->file_path)), 404);

        try {
            $pdf = $convertirDocumentEnPdf->execute($document);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('cours.documents.download', [$cours, $document]);
        }

        return response()->file($pdf, ['Content-Type' => 'application/pdf'])
            ->setContentDisposition('inline', pathinfo($document->nom_original, PATHINFO_FILENAME).'.pdf');
    }

    /**
     * Supprime un document du cours et son fichier physique.
     *
     * @throws AuthorizationException
     */
    public function destroy(Cours $cours, CoursDocument $document): RedirectResponse
    {
        abort_if($document->cours_id !== $cours->id, 404);
        $this->authorize('update', $cours);

        $document->deleteWithFile();

        return back()->with('success', __('document.deleted'));
    }
}
