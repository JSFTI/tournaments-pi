# Authenticating requests

This API is authenticated by sending an **`Authorization`** header with the value **`"Bearer {YOUR_AUTH_KEY}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Token is a JWT token that can be retrieved by logging in to <i>/login</i>
