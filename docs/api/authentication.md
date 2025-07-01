# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_API_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

You can retrieve your API token by making a POST request to `/api/login` with your email and password. The token should be included in the Authorization header as `Bearer {token}`.

## Authentication Flow

1. **Login**: Send POST request to `/api/login` with email and password
2. **Receive Token**: Get the API token from the response
3. **Use Token**: Include the token in the Authorization header for all subsequent requests

## Example

```bash
# Login
curl -X POST http://site-wewingames.test/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password"}'

# Response
{
  "token": "1|abcdef123456...",
  "user": {...}
}

# Use token in requests
curl -X GET http://site-wewingames.test/api/v1/user \
  -H "Authorization: Bearer 1|abcdef123456..."
```