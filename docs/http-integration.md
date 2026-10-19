# Host HTTP integration

The package registers no HTTP routes. The host chooses authentication, middleware, rate
limits, enabled carriers, countries and error presentation. Example controller logic:

```php
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Trianity\PickupPoints\Enums\PointType;
use Trianity\PickupPoints\PickupPoints;

public function index(Request $request, PickupPoints $catalog)
{
    $input = $request->validate([
        'provider' => ['required', Rule::in(['gls'])],
        'country' => ['required', Rule::in(['HU'])],
        'query' => ['nullable', 'string', 'max:200'],
        'type' => ['nullable', Rule::enum(PointType::class)],
        'limit' => ['sometimes', 'integer', 'between:1,100'],
    ]);

    $type = isset($input['type']) ? PointType::from($input['type']) : null;
    $snapshot = $catalog->snapshot($input['provider'], $input['country']);

    return response()->json([
        'data' => $catalog->search(
            $input['provider'], $input['country'], $input['query'] ?? '',
            $type, (int) ($input['limit'] ?? 50),
        ),
        'meta' => ['synced_at' => $snapshot->syncedAt->format(DATE_ATOM)],
    ]);
}
```

Map `CatalogException` to an appropriate unavailable response in your application. Do not
trigger synchronous remote downloads when search or checkout discovers a missing catalog.
Schedule a sync and show an actionable shipping alternative. The metadata timestamp in this
example is informational: a refresh can occur between the two reads. For a strictly consistent
response, filter the one retrieved snapshot instead.

## Checkout confirmation

Validate `provider`, `country` and `point_id`, then call `find()` on the server. Reject a missing
point, disallowed provider/country/type or a point ineligible for the shipment under your rules.
Point membership alone does not establish current availability or COD capability. Review
`holiday`, `lockerSaturation` and provider features where applicable.

Inside the host's order creation transaction, persist the validated identity and an address
snapshot, not just a foreign reference to today's catalog. A later refresh must not rewrite
past orders. Retrying checkout must follow the host's idempotency rules.

Treat names and metadata as untrusted external text; use normal escaped rendering, never
insert them as HTML. Do not send API credentials to the frontend.
