# Dropshipper Categories API Documentation

This documentation describes the GET Categories endpoints designed for dropshippers to query active product categories and subcategories.

---

## 1. External Secure API

Exposed for third-party integrations requiring secure server-to-server communication using IP security and HMAC authentication.

### Endpoint Details
* **URL:** `/api/dropshipping/categories`
* **Method:** `GET`
* **Authentication:** HMAC authentication (`hmac.auth`) and IP security (`ip.security`)

### Headers
* `Content-Type: application/json`
* `Accept: application/json`
* `api-key: <YOUR_API_KEY>`
* `api-signature: <HMAC_SHA256_SIGNATURE>`

---

## 2. Dashboard Panel API

Exposed for frontend panel dashboards authenticated via Laravel Sanctum session or bearer token.

### Endpoint Details
* **URL:** `/api/dropshipper/categories`
* **Method:** `GET`
* **Authentication:** Sanctum authentication (`auth:sanctum`) and Dropshipper approval check (`dropshipper.approved`)

### Headers
* `Content-Type: application/json`
* `Accept: application/json`
* `Authorization: Bearer <SANCTUM_BEARER_TOKEN>`

---

## 3. Query Parameters

Both endpoints support the following query parameters:

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `parent_only` | `boolean` (0 or 1) | No | If set to `1` or `true`, returns only the top-level parent categories instead of all active categories. Defaults to `0`. |

---

## 4. Response Structure

Returns a standard JSON wrapper:

```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "parent_id": null,
      "image": "categories/electronics.png",
      "is_active": true,
      "priority": 1,
      "meta_title": "Electronics Shop",
      "meta_description": "Buy high quality electronics items",
      "meta_keywords": "electronics, tech, phones",
      "show_in_bar": true,
      "bar_icon": "fa-plug",
      "custom_icon": null,
      "show_shop_by_category": true,
      "created_at": "2026-06-25T12:00:00.000000Z",
      "updated_at": "2026-06-25T12:00:00.000000Z",
      "image_url": "http://localhost:8000/storage/products/categories/electronics.png",
      "custom_icon_url": null,
      "children": [
        {
          "id": 2,
          "name": "Mobile Phones",
          "slug": "mobile-phones",
          "parent_id": 1,
          "image": null,
          "is_active": true,
          "priority": 1,
          "meta_title": null,
          "meta_description": null,
          "meta_keywords": null,
          "show_in_bar": false,
          "bar_icon": null,
          "custom_icon": null,
          "show_shop_by_category": false,
          "created_at": "2026-06-25T12:00:00.000000Z",
          "updated_at": "2026-06-25T12:00:00.000000Z",
          "image_url": null,
          "custom_icon_url": null,
          "children": []
        }
      ]
    }
  ]
}
```

---

## 5. Implementation / Integration Examples

### JavaScript Fetch Example
```javascript
const response = await fetch('https://yourdomain.com/api/dropshipper/categories?parent_only=1', {
  method: 'GET',
  headers: {
    'Accept': 'application/json',
    'Authorization': 'Bearer YOUR_SANCTUM_BEARER_TOKEN'
  }
});
const result = await response.json();
console.log(result);
```

### cURL Example
```bash
curl -X GET "https://yourdomain.com/api/dropshipping/categories" \
  -H "Accept: application/json" \
  -H "api-key: YOUR_API_KEY" \
  -H "api-signature: YOUR_HMAC_SIGNATURE"
```
