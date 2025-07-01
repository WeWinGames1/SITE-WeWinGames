# Introduction

WeWinGames Sports Betting API provides endpoints for managing bets, games, users, and sports data. This API uses bearer token authentication via Laravel Sanctum.

<aside>
    <strong>Base URL</strong>: <code>http://site-wewingames.test</code>
</aside>

    This documentation provides comprehensive information about the WeWinGames API.

    ## Base URL
    All API requests should be made to: `/api/v1`

    ## Authentication
    Most endpoints require authentication using a Bearer token. Include the token in the Authorization header:
    ```
    Authorization: Bearer YOUR_API_TOKEN
    ```

    ## Rate Limiting
    API requests are rate limited. The following limits apply:
    - Authenticated users: 120 requests per minute
    - Unauthenticated users: 60 requests per minute

    Rate limit information is included in response headers.

    <aside>As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
    You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).</aside>

