<?php

use App\Models\Classe;
use App\Models\Cours;
use App\Models\CoursDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

uses(RefreshDatabase::class);

/**
 * Crée un enseignant et un cours minimal pour les tests de documents.
 *
 * @return array{enseignant: User, cours: Cours}
 */
function creerScenarioDocument(): array
{
    $enseignant = User::factory()->create(['role' => 'enseignant']);

    $cours = Cours::create([
        'nom_cours' => 'Histoire du Québec',
        'code' => '330-DOC',
        'groupe' => '00001',
        'enseignant_id' => $enseignant->id,
    ]);

    return compact('enseignant', 'cours');
}

test('store refuse un format invalide avec message en francais', function () {
    ['enseignant' => $enseignant, 'cours' => $cours] = creerScenarioDocument();

    $this->actingAs($enseignant)
        ->from("/cours/{$cours->id}")
        ->post("/cours/{$cours->id}/documents", [
            'document' => UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg'),
        ])
        ->assertSessionHasErrors(['document'])
        ->assertSessionHasErrors([
            'document' => __('document.format_error'),
        ]);
});

test('download renvoie le fichier avec le nom original', function () {
    ['enseignant' => $enseignant, 'cours' => $cours] = creerScenarioDocument();

    $this->actingAs($enseignant)
        ->post("/cours/{$cours->id}/documents", [
            'document' => UploadedFile::fake()->create('consignes.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect();

    $document = $cours->documents()->first();

    $this->actingAs($enseignant)
        ->get("/cours/{$cours->id}/documents/{$document->id}/download")
        ->assertOk()
        ->assertDownload('consignes.pdf');
});

test('download retourne 404 si le document n appartient pas au cours', function () {
    ['enseignant' => $enseignant, 'cours' => $cours] = creerScenarioDocument();
    ['cours' => $autreCours] = creerScenarioDocument();

    $this->actingAs($enseignant)
        ->post("/cours/{$cours->id}/documents", [
            'document' => UploadedFile::fake()->create('consignes.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect();

    $document = $cours->documents()->first();

    $this->actingAs($enseignant)
        ->get("/cours/{$autreCours->id}/documents/{$document->id}/download")
        ->assertNotFound();
});

test('destroy supprime le document du cours', function () {
    ['enseignant' => $enseignant, 'cours' => $cours] = creerScenarioDocument();

    $this->actingAs($enseignant)
        ->post("/cours/{$cours->id}/documents", [
            'document' => UploadedFile::fake()->create('consignes.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect();

    $document = $cours->documents()->first();

    $this->actingAs($enseignant)
        ->delete("/cours/{$cours->id}/documents/{$document->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('cours_documents', ['id' => $document->id]);
});

test('store accepte un document pdf', function () {
    ['enseignant' => $enseignant, 'cours' => $cours] = creerScenarioDocument();

    $this->actingAs($enseignant)
        ->post("/cours/{$cours->id}/documents", [
            'document' => UploadedFile::fake()->create('consignes.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('cours_documents', [
        'cours_id' => $cours->id,
        'enseignant_id' => $enseignant->id,
        'nom_original' => 'consignes.pdf',
    ]);
});

test('destroy refuse un document appartenant à un autre cours', function () {
    ['enseignant' => $enseignant, 'cours' => $cours] = creerScenarioDocument();
    $autreCours = Cours::create([
        'nom_cours' => 'Autre cours',
        'code' => '330-AUT',
        'groupe' => '00002',
        'enseignant_id' => $enseignant->id,
    ]);

    $this->actingAs($enseignant)
        ->post("/cours/{$autreCours->id}/documents", [
            'document' => UploadedFile::fake()->create('plan.pdf', 100, 'application/pdf'),
        ]);
    $document = $autreCours->documents()->firstOrFail();

    $this->actingAs($enseignant)
        ->delete("/cours/{$cours->id}/documents/{$document->id}")
        ->assertNotFound();

    $this->assertDatabaseHas('cours_documents', ['id' => $document->id]);
});

// ─── Aperçu PDF ───────────────────────────────────────────────────────────────

/**
 * Dépose directement un document dans le cours (sans passer par la validation d'upload).
 *
 * Permet de déposer un vrai .docx généré ou un fichier corrompu. Les fichiers vont
 * dans un dossier réservé aux tests, vidé par le afterEach ci-dessous.
 */
function deposerDocumentDeTest(Cours $cours, string $nomOriginal, string $contenu): CoursDocument
{
    $extension = pathinfo($nomOriginal, PATHINFO_EXTENSION);
    $chemin = 'images/cours-tests/'.Str::uuid().".{$extension}";

    File::ensureDirectoryExists(dirname(public_path($chemin)));
    File::put(public_path($chemin), $contenu);

    return $cours->documents()->create([
        'enseignant_id' => $cours->enseignant_id,
        'nom_original' => $nomOriginal,
        'file_path' => $chemin,
        'type' => $extension,
        'taille' => strlen($contenu),
    ]);
}

/**
 * Retourne le contenu binaire d'un .docx minimal généré avec PhpWord.
 */
function contenuDocxDeTest(): string
{
    $phpWord = new PhpWord;
    $phpWord->addSection()->addText('Consignes du travail final');

    $chemin = tempnam(sys_get_temp_dir(), 'docx');
    IOFactory::createWriter($phpWord, 'Word2007')->save($chemin);
    $contenu = File::get($chemin);
    File::delete($chemin);

    return $contenu;
}

/**
 * Inscrit un nouvel étudiant dans une classe du cours.
 */
function inscrireEtudiantAuCours(Cours $cours): User
{
    $etudiant = User::factory()->create(['role' => 'etudiant']);
    Classe::create(['cours_id' => $cours->id])->etudiants()->attach($etudiant->id);

    return $etudiant;
}

beforeEach(function () {
    Storage::fake('local');
});

afterEach(function () {
    File::deleteDirectory(public_path('images/cours-tests'));
});

test('apercu affiche un pdf dans le navigateur sans le telecharger', function () {
    ['enseignant' => $enseignant, 'cours' => $cours] = creerScenarioDocument();
    $document = deposerDocumentDeTest($cours, 'consignes.pdf', '%PDF-1.4 test');

    $reponse = $this->actingAs($enseignant)
        ->get("/cours/{$cours->id}/documents/{$document->id}/apercu")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect($reponse->headers->get('Content-Disposition'))->toStartWith('inline');
});

test('apercu convertit un docx en pdf et met la conversion en cache', function () {
    ['enseignant' => $enseignant, 'cours' => $cours] = creerScenarioDocument();
    $document = deposerDocumentDeTest($cours, 'consignes.docx', contenuDocxDeTest());

    $reponse = $this->actingAs($enseignant)
        ->get("/cours/{$cours->id}/documents/{$document->id}/apercu")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect($reponse->headers->get('Content-Disposition'))->toContain('inline', 'consignes.pdf');
    Storage::disk('local')->assertExists($document->cheminApercuPdf());
    expect(Storage::disk('local')->get($document->cheminApercuPdf()))->toStartWith('%PDF');
});

test('apercu redirige vers le telechargement si la conversion echoue', function () {
    ['enseignant' => $enseignant, 'cours' => $cours] = creerScenarioDocument();
    $document = deposerDocumentDeTest($cours, 'corrompu.docx', 'pas un document word');

    $this->actingAs($enseignant)
        ->get("/cours/{$cours->id}/documents/{$document->id}/apercu")
        ->assertRedirect("/cours/{$cours->id}/documents/{$document->id}/download");

    Storage::disk('local')->assertMissing($document->cheminApercuPdf());
});

test('un etudiant inscrit peut afficher et telecharger les documents du cours', function () {
    ['cours' => $cours] = creerScenarioDocument();
    $etudiant = inscrireEtudiantAuCours($cours);
    $document = deposerDocumentDeTest($cours, 'consignes.pdf', '%PDF-1.4 test');

    $this->actingAs($etudiant)
        ->get("/cours/{$cours->id}/documents/{$document->id}/apercu")
        ->assertOk();

    $this->actingAs($etudiant)
        ->get("/cours/{$cours->id}/documents/{$document->id}/download")
        ->assertOk()
        ->assertDownload('consignes.pdf');
});

test('un etudiant non inscrit ne peut pas afficher les documents du cours', function () {
    ['cours' => $cours] = creerScenarioDocument();
    ['cours' => $autreCours] = creerScenarioDocument();
    $etudiant = inscrireEtudiantAuCours($autreCours);
    $document = deposerDocumentDeTest($cours, 'consignes.pdf', '%PDF-1.4 test');

    $this->actingAs($etudiant)
        ->get("/cours/{$cours->id}/documents/{$document->id}/apercu")
        ->assertForbidden();
});

test('apercu retourne 404 si le document n appartient pas au cours', function () {
    ['enseignant' => $enseignant, 'cours' => $cours] = creerScenarioDocument();
    $autreCours = Cours::create([
        'nom_cours' => 'Autre cours',
        'code' => '330-APE',
        'groupe' => '00003',
        'enseignant_id' => $enseignant->id,
    ]);
    $document = deposerDocumentDeTest($autreCours, 'consignes.pdf', '%PDF-1.4 test');

    $this->actingAs($enseignant)
        ->get("/cours/{$cours->id}/documents/{$document->id}/apercu")
        ->assertNotFound();
});

test('supprimer un document supprime aussi son apercu converti', function () {
    ['enseignant' => $enseignant, 'cours' => $cours] = creerScenarioDocument();
    $document = deposerDocumentDeTest($cours, 'consignes.docx', contenuDocxDeTest());

    $this->actingAs($enseignant)->get("/cours/{$cours->id}/documents/{$document->id}/apercu");
    Storage::disk('local')->assertExists($document->cheminApercuPdf());

    $this->actingAs($enseignant)
        ->delete("/cours/{$cours->id}/documents/{$document->id}")
        ->assertRedirect();

    Storage::disk('local')->assertMissing($document->cheminApercuPdf());
});
