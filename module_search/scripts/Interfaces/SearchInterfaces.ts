interface SearchResult {
    description: string;
    icon: string;
    link: string;
    module: string;
    score: string;
    systemid: string;
    additionalInfos: string;
    lastModifiedBy: string;
    lastModifiedTime: string;
    actions: string;
}
interface FilterModule{
    module: string;
    id: number;
}
interface User {
    icon: string;
    label: string;
    systemid: string;
    title: string;
    value: string;
}
export { SearchResult, FilterModule, User }
