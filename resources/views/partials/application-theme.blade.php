<style id="application-theme">
    html:root {
        --exad-primary: {{ $applicationSettings->primary_color }};
        --exad-primary-dark: {{ $applicationSettings->secondary_color }};
        --exad-secondary: {{ $applicationSettings->secondary_color }};
        --exad-button: {{ $applicationSettings->button_color }};
        --exad-avatar: {{ $applicationSettings->avatar_color }};
        --exad-accent: {{ $applicationSettings->accent_color }};
        --exad-purple: {{ $applicationSettings->accent_color }};
        --exad-sidebar: {{ $applicationSettings->sidebar_start_color }};
        --exad-sidebar-deep: {{ $applicationSettings->sidebar_end_color }};
        --exad-blue: {{ $applicationSettings->primary_color }};
        --exad-blue-deep: {{ $applicationSettings->secondary_color }};
        --exad-blue-accent: {{ $applicationSettings->accent_color }};
        --bs-primary: {{ $applicationSettings->button_color }};
    }
</style>
