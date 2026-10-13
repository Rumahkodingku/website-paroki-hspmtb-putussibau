export type PublicParishProfile = {
    name: string | null;
    short_description: string | null;
    description: string | null;
    history_description: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    website: string | null;
    established_year: number | null;
    logo: string | null;
    logo_alt: string | null;
    cover: string | null;
    cover_alt: string | null;
    map_url: string | null;
    video_url: string | null;
};

export type PublicHistoryItem = {
    id: number;
    title: string;
    year: number | null;
    date: string | null;
    description: string | null;
    image: string | null;
    image_alt: string | null;
};

export type PublicServiceArea = {
    content: string | null;
    map: string | null;
    map_url: string | null;
};

export type PublicPastor = {
    id: number;
    name: string;
    title: string | null;
    photo: string | null;
    photo_alt: string | null;
    bio: string | null;
    position: string | null;
    position_label: string | null;
    start_year: number | null;
    end_year: number | null;
};

export type PublicPastorAssignment = {
    id: number;
    name: string;
    title: string | null;
    position: string;
    position_label: string;
    start_year: number | null;
    end_year: number | null;
    is_current: boolean;
};

export type PublicBoardMember = {
    id: number;
    name: string;
    position: string;
    photo: string | null;
    photo_alt: string | null;
    description: string | null;
    sort_order: number;
};

export type PublicBoardPeriod = {
    id: number;
    name: string;
    start_year: number | null;
    end_year: number | null;
    description: string | null;
    is_active: boolean;
    members: PublicBoardMember[];
};

export type ProfilSections = {
    sejarah: boolean;
    wilayah: boolean;
    pastor: boolean;
    struktur: boolean;
};
