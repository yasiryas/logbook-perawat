export type Unit = {
    id: number;
    name: string;
    slug: string;
    nurses_count?: number;
    indicators_count?: number;
};

export type Nurse = {
    id: number;
    name: string;
    nip: string;
    pk: string;
    unit_id: number;
    unit?: { id: number; name: string };
    user_id?: number | null;
};

export type IndicatorRow = {
    id: number;
    sort: number;
    name: string;
    target: string;
    is_checkbox?: boolean;
    values?: Record<number, number | null>;
    sum?: number;
    filled_days?: number;
    achievement?: number;
    monthly?: number[];
    weight?: number;
    score?: number;
};

export type CategoryGroup = {
    id: number;
    code: string;
    name: string;
    weight_percent: number;
    indicators: IndicatorRow[];
    achievement?: number;
};

export type AssessmentItem = {
    id: number;
    indicator_id: number;
    name: string;
    target: string;
    achievement: number;
    weight: number;
    score: number;
};

export type AssessmentCategory = {
    id: number;
    code: string;
    name: string;
    weight_percent: number;
    rows: AssessmentItem[];
    achievement: number;
};

export type AnnualAssessmentRow = {
    id: number;
    name: string;
    nip: string;
    pk: string;
    unit: string;
    year: number;
    status: 'draft' | 'final';
    total: number;
};