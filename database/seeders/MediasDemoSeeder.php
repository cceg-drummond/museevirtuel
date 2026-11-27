<?php

namespace Database\Seeders;

use App\Models\Groupe;
use App\Models\GroupeMedia;
use Illuminate\Database\Seeder;

/**
 * Médias de démonstration (photos, audio, documents) sur trois groupes de prof@demo.com.
 *
 * Les fichiers sont générés sans GD : PNG unis, sons WAV courts et PDF minimal.
 */
class MediasDemoSeeder extends Seeder
{
    /**
     * Médias à créer par rang de groupe : [type, nom de fichier, couleur RVB ou fréquence en Hz].
     *
     * @var list<list<array{0: string, 1: string, 2: mixed}>>
     */
    private const MEDIAS = [
        [
            ['photo', 'ferme-familiale-1955.png', [176, 137, 84]],
            ['photo', 'ecole-de-rang.png', [94, 128, 160]],
            ['photo', 'eglise-du-village.png', [140, 140, 140]],
            ['audio', 'entrevue-partie-1.wav', 440],
            ['audio', 'entrevue-partie-2.wav', 523],
            ['document', 'plan-de-travail.pdf', 'Plan de travail du groupe'],
        ],
        [
            ['photo', 'expo-67.png', [201, 160, 64]],
            ['audio', 'souvenirs-expo.wav', 392],
            ['document', 'transcription-entrevue.pdf', 'Transcription de l\'entrevue'],
            ['document', 'fiche-de-recherche.pdf', 'Fiche de recherche'],
        ],
        [
            ['photo', 'filature-drummondville.png', [120, 90, 120]],
            ['photo', 'magasin-general.png', [60, 130, 110]],
        ],
    ];

    /**
     * Ajoute les médias aux trois premiers groupes des cours de prof@demo.com.
     */
    public function run(): void
    {
        $groupes = Groupe::whereHas('classe.cours.enseignant', fn ($q) => $q->where('email', 'prof@demo.com'))
            ->with('membres:id')
            ->orderBy('id')
            ->limit(count(self::MEDIAS))
            ->get();

        if ($groupes->isEmpty()) {
            $this->command?->warn('Aucun groupe pour prof@demo.com : lancer DemoSeeder et GroupeDemoSeeder d\'abord.');
        } else {
            $groupes->values()->each(fn (Groupe $groupe, int $rang) => $this->seederGroupe($groupe, self::MEDIAS[$rang]));
        }
    }

    /**
     * Crée les fichiers et les médias d'un groupe s'ils n'existent pas déjà.
     *
     * @param  list<array{0: string, 1: string, 2: mixed}>  $medias
     */
    private function seederGroupe(Groupe $groupe, array $medias): void
    {
        $dossier = "images/groupes/{$groupe->id}";
        $auteurs = $groupe->membres->pluck('id')->whenEmpty(fn ($ids) => $ids->push($groupe->created_by));

        if (! is_dir(public_path($dossier))) {
            mkdir(public_path($dossier), 0755, true);
        }

        foreach ($medias as $index => [$type, $nom, $parametre]) {
            $chemin = "{$dossier}/seed-{$nom}";
            $contenu = match ($type) {
                'photo' => $this->png($parametre),
                'audio' => $this->wav($parametre),
                default => $this->pdf($parametre),
            };

            file_put_contents(public_path($chemin), $contenu);

            GroupeMedia::firstOrCreate(
                ['groupe_id' => $groupe->id, 'nom_original' => $nom],
                [
                    'user_id' => $auteurs[$index % $auteurs->count()],
                    'file_path' => $chemin,
                    'type' => $type,
                    'taille' => strlen($contenu),
                ],
            );
        }
    }

    /**
     * Génère une image PNG unie de 480 × 360.
     *
     * @param  array{0: int, 1: int, 2: int}  $rvb
     */
    private function png(array $rvb): string
    {
        [$largeur, $hauteur] = [480, 360];
        $ligne = "\0".str_repeat(pack('C3', ...$rvb), $largeur);
        $chunk = fn (string $nom, string $donnees) => pack('N', strlen($donnees)).$nom.$donnees
            .pack('N', crc32($nom.$donnees));

        return "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $largeur, $hauteur, 8, 2, 0, 0, 0))
            .$chunk('IDAT', gzcompress(str_repeat($ligne, $hauteur)))
            .$chunk('IEND', '');
    }

    /**
     * Génère un son WAV de deux secondes (8 kHz, mono, 8 bits) à la fréquence donnée.
     */
    private function wav(int $frequence): string
    {
        $echantillons = '';

        for ($i = 0; $i < 16000; $i++) {
            $echantillons .= chr((int) (128 + 60 * sin(2 * M_PI * $frequence * $i / 8000)));
        }

        return 'RIFF'.pack('V', 36 + strlen($echantillons)).'WAVE'
            .'fmt '.pack('VvvVVvv', 16, 1, 1, 8000, 8000, 1, 8)
            .'data'.pack('V', strlen($echantillons)).$echantillons;
    }

    /**
     * Génère un PDF d'une page contenant un titre.
     */
    private function pdf(string $titre): string
    {
        $texte = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], mb_convert_encoding($titre, 'ISO-8859-1', 'UTF-8'));
        $flux = "BT /F1 20 Tf 72 720 Td ({$texte}) Tj ET";
        $objets = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($flux)." >>\nstream\n{$flux}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        ];

        $pdf = "%PDF-1.4\n";
        $positions = [];

        foreach ($objets as $index => $objet) {
            $positions[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$objet}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref
0 '.(count($objets) + 1)."\n0000000000 65535 f \n";

        foreach ($positions as $position) {
            $pdf .= sprintf("%010d 00000 n \n", $position);
        }

        return $pdf.'trailer << /Size '.(count($objets) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }
}
