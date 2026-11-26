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

export type ClasseEnseignant = {
    id: number;
    numero: number;
    nom: string | null;
    jour_semaine: string | null;
    plage_horaire: string | null;
    etudiants_count: number;
    groupes_count: number;
    cours: {
        id: number;
        nom_cours: string;
        code: string;
        groupe: string;
        session: 'hiver' | 'ete' | 'automne';
        annee: number;
        is_verrouille: boolean;
    };
};

export type GroupeEnseignant = {
    id: number;
    numero: number;
    membres_count: number;
    projets_count: number;
    temoin: { id: number; prenom: string; nom: string } | null;
    classe: { id: number; numero: number; nom: string | null };
    cours: { id: number; nom_cours: string; code: string; groupe: string };
};

export type ProjetEnseignant = {
    id: number;
    titre_projet: string | null;
    statut: StatutProjet['valeur'];
    remis_le: string | null;
    date_remise: string | null;
    type_projet: { id: number; nom: string };
    groupe: { id: number; numero: number };
    classe: { id: number; numero: number; nom: string | null };
    cours: { id: number; nom_cours: string; code: string; groupe: string };
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

export type VisioEnseignant = ProchaineVisio & {
    groupe_id: number | null;
    ended_at: string | null;
    has_recording: boolean;
    recording_stream_url: string | null;
};

export type ConversationEnseignant = {
    groupe: { id: number; numero: number };
    classe: { id: number; numero: number; nom: string | null };
    cours: { id: number; nom_cours: string; code: string; groupe: string };
    temoin: { id: number; prenom: string; nom: string } | null;
    messages_count: number;
    dernier_message: {
        extrait: string;
        auteur: { prenom: string; nom: string } | null;
        envoye_le: string;
    } | null;
};

export type EcheancierEnseignant = {
    cours: { id: number; nom_cours: string; code: string; groupe: string };
    etudiants_count: number;
    etapes: {
        id: number;
        semaine: number;
        periode: number | null;
        etape: string;
        is_done: boolean;
        etudiants_termines: number;
    }[];
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
