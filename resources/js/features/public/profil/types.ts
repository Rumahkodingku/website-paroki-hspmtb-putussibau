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
