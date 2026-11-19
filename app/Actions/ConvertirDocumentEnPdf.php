<?php

namespace App\Actions;

use App\Models\CoursDocument;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Exception\Exception as PhpWordException;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

class ConvertirDocumentEnPdf
{
    /**
     * Retourne le chemin absolu d'une version PDF affichable du document.
     *
     * Un PDF est servi tel quel. Un document Word est converti au premier appel,
     * puis la conversion est réutilisée : elle ne change jamais puisqu'un document
     * déposé n'est pas modifiable.
     *
     * @throws PhpWordException si PhpWord ne parvient pas à lire ou à convertir le document
     */
    public function execute(CoursDocument $document): string
    {
        $source = public_path($document->file_path);

        if (strtolower(pathinfo($source, PATHINFO_EXTENSION)) === 'pdf') {
            return $source;
        }

        $disque = Storage::disk('local');
        $cible = $document->cheminApercuPdf();

        if (! $disque->exists($cible)) {
            $disque->makeDirectory(dirname($cible));
            $this->convertir($source, $disque->path($cible));
        }

        return $disque->path($cible);
    }

    /**
     * Convertit un fichier Word en PDF avec le rendu DomPDF de PhpWord.
     *
     * L'écriture passe par un fichier temporaire renommé à la fin : deux aperçus
     * simultanés ne peuvent pas servir un PDF à moitié écrit.
     *
     * @throws PhpWordException
     */
    private function convertir(string $source, string $cible): void
    {
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

        // Les .doc (Word 97-2003) ont leur propre lecteur ; PhpWord ne devine pas le format.
        $lecteur = strtolower(pathinfo($source, PATHINFO_EXTENSION)) === 'doc' ? 'MsDoc' : 'Word2007';

        $temporaire = $cible.'.tmp';
        IOFactory::createWriter(IOFactory::load($source, $lecteur), 'PDF')->save($temporaire);
        rename($temporaire, $cible);
    }
}
