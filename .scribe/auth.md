# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer Bearer {YOUR_API_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

You can retrieve your API token by making a POST request to `/api/login` with your email and password. The token should be included in the Authorization header as `Bearer {token}`.
