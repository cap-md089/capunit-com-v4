interface Qualification {
	expires: boolean | string | null; // MM/YY
	evaluator: boolean;
	supervised: boolean;
	nims: boolean;
	aircraft: boolean;
	active: boolean;

	name: string;
}

interface Card {
	name: string;
	unit: string;
	CAPID: number;
	height: number;
	weight: number;
	eyes: string;
	hair: string;

	quals: Qualification[];
	driversLicense: {
		expires: string;
		details: string;
	};
}
