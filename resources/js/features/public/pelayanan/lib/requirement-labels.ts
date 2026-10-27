export const REQUIREMENT_CONDITION_LABELS: Record<string, string> = {
    required: 'Wajib',
    conditional: 'Jika berlaku',
    upon_request: 'Bila diminta',
};

export const REQUIREMENT_AUDIENCE_LABELS: Record<string, string> = {
    applicant: 'Calon penerima sakramen',
    parents: 'Orang tua',
    godparents: 'Wali baptis',
    sponsor: 'Pendamping',
    household: 'Keluarga',
    other: 'Pihak terkait',
};

export function getRequirementAudienceLabel(value: string): string {
    return REQUIREMENT_AUDIENCE_LABELS[value] ?? value;
}
