// Dropdown and checkbox lists used by the survey. Edit these to match your college.

export const regions = [
    'NCR - National Capital Region',
    'CAR - Cordillera Administrative Region',
    'Region I - Ilocos Region',
    'Region II - Cagayan Valley',
    'Region III - Central Luzon',
    'Region IV-A - CALABARZON',
    'MIMAROPA Region',
    'Region V - Bicol Region',
    'Region VI - Western Visayas',
    'Region VII - Central Visayas',
    'Region VIII - Eastern Visayas',
    'Region IX - Zamboanga Peninsula',
    'Region X - Northern Mindanao',
    'Region XI - Davao Region',
    'Region XII - SOCCSKSARGEN',
    'Region XIII - Caraga',
    'BARMM - Bangsamoro Autonomous Region',
];

export const civilStatuses = [
    'Single',
    'Married',
    'Widowed',
    'Separated',
    'Divorced',
];

export const courses = [
    'Associate in Computer Technology',
    'Bachelor of Library in Information Science',
    'Bachelor of Science in Information and Technology',
];

const thisYear = new Date().getFullYear();
export const graduationYears = Array.from(
    { length: thisYear - 1966 + 1 },
    (_, i) => String(thisYear - i),
);

export const exams = [
    { value: 'let', label: 'Licensure Examination for Teachers' },
    { value: 'csc_professional', label: 'CSC Professional' },
    { value: 'csc_sub_professional', label: 'CSC Sub-Professional' },
];

// Keys must match REASON_KEYS in SurveyController.php. Order is row by row, left cell then right cell.
export const courseReasons = [
    {
        key: 'high_grades_course',
        label: 'High grades in the course or subject area(s) related to the course',
    },
    { key: 'good_grades_hs', label: 'Good grades in high school' },
    { key: 'parents_relatives', label: 'Influence of parents or relatives' },
    { key: 'peer_influence', label: 'Peer Influence' },
    { key: 'role_model', label: 'Inspired by a role model' },
    { key: 'passion', label: 'Strong passion for the profession' },
    { key: 'immediate_employment', label: 'Prospect for immediate employment' },
    { key: 'prestige', label: 'Status or prestige of the profession' },
    {
        key: 'course_availability',
        label: 'Availability of course offering in chosen institution',
    },
    { key: 'career_advancement', label: 'Prospect of career advancement' },
    { key: 'affordable', label: 'Affordable for the family' },
    { key: 'compensation', label: 'Prospect of attractive compensation' },
    { key: 'abroad', label: 'Opportunity for employment abroad' },
    { key: 'no_choice', label: 'No particular choice or no better idea' },
];

export const pursueReasons = [
    { value: 'promotion', label: 'For promotion' },
    {
        value: 'professional_development',
        label: 'For professional development',
    },
    { value: 'other', label: 'Other', other: true },
];

export const yesNo = [
    { value: 'yes', label: 'Yes' },
    { value: 'no', label: 'No' },
];

export const employedOptions = [
    { value: 'yes', label: 'Yes' },
    { value: 'no', label: 'No' },
    { value: 'never', label: 'Never Employed' },
];

export const notEmployedReasons = [
    { value: 'advance_study', label: 'Advance or further study' },
    {
        value: 'family_concern',
        label: 'Family concern and decided not to find a job',
    },
    { value: 'health', label: 'Health-related reason(s)' },
    { value: 'no_experience', label: 'Lack of work experience' },
    { value: 'no_opportunity', label: 'No job opportunity' },
    { value: 'did_not_look', label: 'Did not look for a job' },
    { value: 'other', label: 'Other', other: true },
];

export const employmentStatuses = [
    { value: 'regular', label: 'Regular or Permanent' },
    { value: 'temporary', label: 'Temporary' },
    { value: 'casual', label: 'Casual' },
    { value: 'contractual', label: 'Contractual' },
    { value: 'self_employed', label: 'Self-employed' },
];

export const sectors = [
    { value: 'government', label: 'Government' },
    { value: 'private', label: 'Private' },
    { value: 'entrepreneurial', label: 'Entrepreneurial / Freelance Work' },
    { value: 'other', label: 'Other', other: true },
];

export const businessLines = [
    'Information Technology',
    'Manufacturing',
    'Agriculture, Hunting and Forestry',
    'Fishing',
    'Mining and Quarrying',
    'Librarian',
    'Electricity, Gas and Water Supply',
    'Construction',
    'Wholesale and Retail Trade, repair of motor vehicles, motorcycles and personal and household goods',
    'Hotels and Restaurants',
    'Transport Storage and Communication',
    'Financial Intermediation',
    'Real Estate, Renting and Business Activities',
    'Public Administration and Defense; Compulsory, Social Security',
    'Education',
    'Health and Social Work',
    'Other Community, Social and Personal Service Activities',
    'Private Households with Employed Persons',
    'Extra-territorial Organizations and Bodies',
];

export const workLines = [
    'Librarian',
    'Software Developer',
    'Cloud Architect',
    'Web Developer',
    'Mobile Application Developer',
    'Data Architect',
    'Network Administrator',
    'Systems Administrator',
    'Database Administrator',
    'Help Desk/Support Specialist',
    'Cybersecurity Analyst',
    'Data Analyst',
    'Data Scientist',
    'IT Project Manager',
    'IT Consultant',
    'UX/UI Designer',
    'Other',
];

export const placeOfWork = [
    { value: 'local', label: 'Local' },
    { value: 'abroad', label: 'Abroad' },
];

export const jobReasons = [
    { value: 'salaries', label: 'Salaries & benefits' },
    { value: 'career_challenge', label: 'Career challenge' },
    { value: 'special_skills', label: 'Related to special skills' },
    { value: 'proximity', label: 'Proximity to residence' },
    { value: 'other', label: 'Other', other: true },
];

export const timeToLand = [
    'Less than a month',
    '1 to 6 months',
    '7 to 11 months',
    '1 year to less than 2 years',
    '2 years to less than 3 years',
    '3 years to less than 4 years',
];

export const timeStayed = [
    'Less than 6 months',
    '6 months to less than 1 year',
    '1 year to less than 2 years',
    '2 years to less than 3 years',
    '3 years or more',
];

export const jobLevels = [
    'Rank or Clerical',
    'Professional, Technical or Supervisory',
    'Managerial or Executive',
    'Self-employed',
];

export const howFound = [
    'Response to an advertisement',
    'As walk-in applicant',
    'Recommended by someone',
    'Information from friends',
    "Arranged by school's job placement officer",
    'Family business',
    'Job Fair or Public Employment Service Office (PESO)',
];

export const earnings = [
    'Below P5,000',
    'P5,000 to less than P10,000',
    'P10,000 to less than P15,000',
    'P15,000 to less than P20,000',
    'P20,000 to less than P25,000',
    'P25,000 and above',
];

export const competencies = [
    { value: 'communication', label: 'Communication skills' },
    { value: 'human_relations', label: 'Human Relations skills' },
    { value: 'entrepreneurial', label: 'Entrepreneurial skills' },
    { value: 'it', label: 'Information Technology skills' },
    { value: 'problem_solving', label: 'Problem-solving skills' },
    { value: 'critical_thinking', label: 'Critical Thinking skills' },
    { value: 'other', label: 'Other', other: true },
];

/** Turns a plain list into radio choices, plus an "Other:" choice with a text box. */
export const withOther = (list: string[]) => [
    ...list.map((value) => ({ value, label: value })),
    { value: 'other', label: 'Other', other: true },
];
