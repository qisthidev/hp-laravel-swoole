# Indonesian Administrative Regions API

A comprehensive Laravel-based API providing data about Indonesian administrative regions including provinces, regencies/cities, districts, and villages with postal codes and geographic boundaries.

## Features

- **Complete Administrative Hierarchy**: Provinces, regencies/cities, districts, and villages
- **Postal Code Integration**: Comprehensive postal code data with geographic coordinates
- **Geographic Boundaries**: GeoJSON support for mapping and visualization
- **Advanced Search**: Global search across regions and postal codes
- **Autocomplete**: Real-time suggestions for region names
- **Data Export**: Multiple formats (CSV, JSON, GeoJSON, Shapefile)
- **Statistics & Analytics**: Coverage statistics and data insights
- **Rate Limiting**: Configurable rate limits for different endpoint types
- **Caching**: Redis-based caching for improved performance
- **Spatial Support**: MySQL spatial extensions for geographic queries

## Installation

### Prerequisites

- PHP 8.2+
- Laravel 12.0+
- MySQL 8.0+ with spatial extensions
- Redis (for caching)
- Composer

### Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd <project-directory>
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database configuration**
   Update your `.env` file with database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=indonesia_api
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Seed the database**
   ```bash
   php artisan db:seed --class=IndonesianAdministrativeDataSeeder
   ```

7. **Start the server**
   ```bash
   php artisan serve
   ```

## API Endpoints

### Base URL
```
https://api.data-id.com/v1
```

### Authentication
All endpoints are public and do not require authentication for basic read operations.

### 1. Administrative Regions

#### GET /regions
Get all administrative regions with optional filtering.

**Parameters:**
- `type` (optional): Filter by region type (provinsi, kabupaten, kota, kecamatan, kelurahan, desa)
- `parent_id` (optional): Filter by parent region
- `search` (optional): Search by name or slug
- `include_boundaries` (optional): Include geographic boundaries (default: false)
- `include_postal_codes` (optional): Include postal codes (default: false)
- `page` (optional): Page number for pagination
- `per_page` (optional): Items per page (default: 50, max: 500)

**Example:**
```bash
curl "https://api.data-id.com/v1/regions?type=provinsi&include_boundaries=true"
```

#### GET /regions/{id}
Get a specific administrative region by ID.

**Parameters:**
- `include_boundaries` (optional): Include geographic boundaries
- `include_children` (optional): Include direct children regions
- `include_postal_codes` (optional): Include postal codes

**Example:**
```bash
curl "https://api.data-id.com/v1/regions/dki-jakarta?include_children=true"
```

#### GET /regions/{id}/children
Get direct children regions of a specific region.

**Example:**
```bash
curl "https://api.data-id.com/v1/regions/dki-jakarta/children"
```

#### GET /regions/{id}/ancestors
Get the hierarchical path from province to the specified region.

**Example:**
```bash
curl "https://api.data-id.com/v1/regions/cibinong/ancestors"
```

### 2. Postal Codes

#### GET /postal-codes
Get postal codes with optional filtering.

**Parameters:**
- `code` (optional): Exact postal code match
- `region_id` (optional): Filter by region
- `region_type` (optional): Filter by region type
- `search` (optional): Search in area names
- `coordinates` (optional): Find nearest postal codes (format: lat,lng)
- `radius` (optional): Search radius in km (default: 5, max: 50)

**Example:**
```bash
curl "https://api.data-id.com/v1/postal-codes?coordinates=-6.2088,106.8456&radius=10"
```

#### GET /postal-codes/{code}
Get detailed information for a specific postal code.

**Example:**
```bash
curl "https://api.data-id.com/v1/postal-codes/10110"
```

#### POST /postal-codes/bulk-lookup
Lookup multiple postal codes in a single request.

**Example:**
```bash
curl -X POST "https://api.data-id.com/v1/postal-codes/bulk-lookup" \
  -H "Content-Type: application/json" \
  -d '{"codes": ["10110", "10120", "10130"]}'
```

### 3. Geographic Boundaries

#### GET /boundaries
Get geographic boundaries for regions.

**Parameters:**
- `region_ids` (optional): Comma-separated list of region IDs
- `region_type` (optional): Filter by region type
- `bbox` (optional): Bounding box filter (format: min_lat,min_lng,max_lat,max_lng)
- `precision` (optional): Boundary precision (high, medium, low)
- `format` (optional): Response format (geojson, topojson, wkt)

**Example:**
```bash
curl "https://api.data-id.com/v1/boundaries?format=geojson&region_type=provinsi"
```

#### GET /boundaries/{region_id}
Get boundary for a specific region.

**Example:**
```bash
curl "https://api.data-id.com/v1/boundaries/dki-jakarta?precision=high"
```

### 4. Search and Discovery

#### GET /search
Global search across all regions and postal codes.

**Parameters:**
- `q` (required): Search query
- `type` (optional): Filter by entity type (region, postal_code)
- `region_type` (optional): Filter by region type
- `limit` (optional): Maximum results (default: 20, max: 100)

**Example:**
```bash
curl "https://api.data-id.com/v1/search?q=jakarta&type=region"
```

#### GET /autocomplete
Get autocomplete suggestions for region names.

**Parameters:**
- `q` (required): Partial query string
- `type` (optional): Filter by region type
- `limit` (optional): Maximum suggestions (default: 10, max: 50)

**Example:**
```bash
curl "https://api.data-id.com/v1/autocomplete?q=jak&type=kota"
```

### 5. Statistics and Analytics

#### GET /stats
Get general statistics about the dataset.

**Example:**
```bash
curl "https://api.data-id.com/v1/stats"
```

#### GET /stats/{region_id}
Get statistics for a specific region.

**Example:**
```bash
curl "https://api.data-id.com/v1/stats/dki-jakarta"
```

### 6. Data Export

#### GET /export
Export data in various formats.

**Parameters:**
- `format` (required): Export format (csv, json, geojson, shapefile)
- `region_ids` (optional): Specific regions to export
- `type` (optional): Filter by region type
- `include_boundaries` (optional): Include geometric data
- `compressed` (optional): Return as ZIP archive

**Example:**
```bash
curl "https://api.data-id.com/v1/export?format=csv&type=provinsi&compressed=true"
```

### 7. Data Updates

#### GET /updates
Get information about recent data updates.

**Example:**
```bash
curl "https://api.data-id.com/v1/updates?limit=20"
```

## Data Models

### AdministrativeRegion
```json
{
  "id": "string (unique identifier)",
  "name": "string (official name)",
  "slug": "string (URL-friendly identifier)",
  "type": "enum (provinsi|kabupaten|kota|kecamatan|kelurahan|desa)",
  "code": "string (official government code)",
  "parent_id": "string|null (parent region ID)",
  "postal_codes": "array<string> (list of postal codes)",
  "coordinates": {
    "latitude": "float",
    "longitude": "float"
  },
  "boundaries": "geojson (geographic boundaries)",
  "area": "float (area in km²)",
  "population": "integer|null",
  "description": "string|null",
  "dataset_url": "string|null (link to detailed dataset)",
  "created_at": "datetime",
  "updated_at": "datetime"
}
```

### PostalCode
```json
{
  "code": "string (5-digit postal code)",
  "region_id": "string (administrative region ID)",
  "area_name": "string (specific area name)",
  "coordinates": {
    "latitude": "float",
    "longitude": "float"
  },
  "delivery_office": "string|null (post office name)",
  "created_at": "datetime",
  "updated_at": "datetime"
}
```

### GeographicBoundary
```json
{
  "region_id": "string",
  "geometry": "geojson (polygon/multipolygon)",
  "centroid": {
    "latitude": "float",
    "longitude": "float"
  },
  "bbox": {
    "min_lat": "float",
    "min_lng": "float",
    "max_lat": "float",
    "max_lng": "float"
  },
  "precision": "enum (high|medium|low)",
  "source": "string (data source)"
}
```

## Error Responses

All endpoints return consistent error responses:

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "region_id": ["The region_id field is required."]
    }
  },
  "meta": {
    "timestamp": "2025-01-01T00:00:00Z",
    "request_id": "req_123456789"
  }
}
```

## Rate Limiting

- **Public endpoints**: 1000 requests per hour per IP
- **Search endpoints**: 500 requests per hour per IP
- **Export endpoints**: 100 requests per hour per IP

Rate limit headers are included in all responses:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests in current window
- `X-RateLimit-Reset`: Time when the rate limit resets

## Performance Considerations

1. **Caching**: All endpoints implement Redis caching with appropriate TTL
2. **Database Indexing**: Proper indexes on frequently queried fields
3. **Pagination**: Large datasets are always paginated
4. **Boundary Simplification**: Different precision levels for boundaries
5. **CDN**: Static geographic data served via CDN

## Development

### Running Tests
```bash
php artisan test
```

### Database Seeding
```bash
php artisan db:seed --class=IndonesianAdministrativeDataSeeder
```

### Cache Management
```bash
php artisan cache:clear
php artisan config:clear
```

### Queue Processing (for background jobs)
```bash
php artisan queue:work
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions, please open an issue on GitHub or contact the development team.

## Changelog

### v1.0.0 (2025-01-01)
- Initial release
- Complete administrative hierarchy support
- Postal code integration
- Geographic boundaries with GeoJSON support
- Advanced search and autocomplete
- Data export functionality
- Statistics and analytics
- Rate limiting and caching