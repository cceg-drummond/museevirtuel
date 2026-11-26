// Types des props de la page d'accueil enseignant (Enseignant/Index),

export type CoursEnseignant = {
    id: number;
    nom_cours: string;
    description: string | null;
    code: string;
    groupe: string;
    annee: number;
    session: 'hiver' | 'ete' | 'automne';
    is_verrouille: boolean;
    etudiants_count: number;
    classes_count: number;
    groupes_count: number;
    types_projets_count: number;
    type_cours: 'dep' | 'cours_complementaire' | 'cours_complet' | null;
    taille_equipe_min: number | null;
    taille_equipe_max: number | null;
};

export type ThematiqueEnseignant = {
    id: number;
    nom: string;
    description: string | null;
    periode_historique: string | null;
    enseignant: { id: number; prenom: string; nom: string } | null;
};

export type TravailRemis = {
    id: number;
    titre_projet: string | null;
    remis_le: string;
    classe: { id: number; numero: number; cours_id: number };
    classe_id: number;
    membres: { id: number; prenom: string; nom: string }[];
};

export type Temoin = {
    id: number;
    prenom: string;
    nom: string;
    email: string;
    description: string;
    created_at: string;
    thematiques_choisies: { id: number; nom: string }[];
};

export type ProchaineVisio = {
    id: number;
    titre: string;
    scheduled_at: string;
    started_at: string | null;
    jitsi_room: string;
    cours: { id: number; nom_cours: string; code: string; groupe: string };
    groupe_numero: number | null;
    animateur: { prenom: string; nom: string };
};

export type IndicateursEnseignant = {
    cours: number;
    coursVerrouilles: number;
    classes: number;
    etudiants: number;
    groupes: number;
    groupesAvecTemoin: number;
    temoinsApprouves: number;
    temoinsEnAttente: number;
    visiosAVenir: number;
    visiosCetteSemaine: number;
    publicationsMusee: number;
    publicationsAApprouver: number;
};

export type StatutProjet = {
    valeur: 'en_cours' | 'remis' | 'en_retard' | 'remis_en_retard';
    libelle: string;
    total: number;
};
