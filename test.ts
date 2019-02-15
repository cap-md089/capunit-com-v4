interface Qualification {
	dne: boolean;
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
	DriversLicense: string | null;
}
