interface Window {
    ajaxurl: string;
    CFImages: CFImages;
}

interface ApiResponse {
    success: boolean;
    data?: any;
    error?: string;
}

interface CFImages {
    browserTTL: string;
    cfStatus: boolean;
    domain: string;
    dirURL: string;
    fuzion: boolean;
    hideSidebar: boolean;
    nonce: string;
    settings: Object;
    stats: StatsType;
    cdnEnabled: boolean;
    isNetworkAdmin: boolean;
    integrationData: object;
}

interface SettingsContextType {
    browserTTL: string;
    modules: object;
    setModule: (module: string, value: boolean) => void;
    integrations: object;
    setIntegration: (module: string, setting: string, value: boolean) => void;
    noticeHidden: boolean;
    hideNotice: (hide: boolean) => void;
    hasFuzion: boolean;
    setFuzion: (fuzion: boolean) => void;
    cfConnected: boolean;
    setCfConnected: (status: boolean) => void;
    inProgress: boolean;
    isNetworkAdmin: boolean;
    setInProgress: (status: boolean) => void;
    stats: StatsType;
    setStats: (stats: StatsType) => void;
    domain: string;
    setDomain: (domain: string) => void;
    cdnEnabled: boolean;
    setCdnEnabled: (cdnEnabled: boolean) => void;
}

interface StatsType {
    alt_tags: number;
    api_current: number;
    size_after: number;
    size_before: number;
    synced: number;
    image_ai: number;
}

interface IntegrationOption {
    name: string;
    label: string;
    value: boolean;
    description: string;
}
