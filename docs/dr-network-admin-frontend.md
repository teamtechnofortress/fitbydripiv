# Dr Network Admin Frontend Implementation

Base path: `/api/v1/admin`

Auth: existing Sanctum admin/staff auth group. The controller allows:
- Read: `admin`, `super_admin`, `network_admin`, `clinical_reviewer`, `support`
- Config write: `admin`, `super_admin`, `network_admin`
- Credential/webhook secret write: `admin`, `super_admin`
- Replay/retry support actions: `admin`, `super_admin`, `support`

Important schema notes:
- Flow routing is controlled by `network_state_mappings.flow_id`.
- Video vs async is not a boolean. It is determined by flow key, for example `ola_health_video_consultation` vs `ola_health_async_review`.
- Intake question sets are product-scoped by product slug in `product_code`. Frontend may submit `product_id`; API converts it to slug.
- Intake question sets may also be scoped by `state_code`; runtime resolution ranks flow + product + state specificity and falls back to `*`.
- Document rules are flow-scoped by `flow_key`, not `flow_id`.
- Flow fees:
  - `network_fee_amount`: what the network charges the business. Stored for margin/reporting.
  - `patient_fee_amount`: amount added to the order total and charged to the patient.
  - Orders snapshot both as `dr_network_fee_amount` and `dr_network_patient_fee_amount`.

## Recommended Screen Map

1. Network List
   - Uses `GET /dr-networks`
   - Actions: create, open detail, status edit.

2. Network Detail
   - Uses `GET /dr-networks/{network}`
   - Tabs:
     - Credentials
     - Flows
     - State Routing
     - Product Mapping Matrix
     - Question Sets
     - Document Rules
     - Webhook and Monitoring

3. Product Mapping Detail
   - Opened from matrix cell.
   - Shows mappings, question set editor, document rules filtered by network/product/flow.

Recommended frontend routes:
- `/admin/dr-networks/:networkId/flows/:flowId/steps`
  - Flow steps and content coverage.
  - Uses `GET /dr-networks/{network}/flows/{flow}/content-coverage`.
- `/admin/dr-networks/:networkId/products/:productId/flows/:flowId`
  - Product Mapping Detail.
  - Tabs: Service Mapping, Questions, Documents.
- `/admin/dr-networks/:networkId/question-sets/:setId`
  - Full Question Set Builder.

## Money Handling

Use decimal inputs with 2 decimals. Treat API values as strings or decimal-safe numbers in UI. Do not use floating point math for display totals in frontend state unless rounded to 2 decimals at every operation.

Display order total:

```text
base_amount = product base amount + dr_network_patient_fee_amount
final_amount = product final amount + dr_network_patient_fee_amount - coupon_discount_amount
price = final_amount
```

The admin should set `patient_fee_amount` on the flow. The customer checkout uses the order snapshot, not the live flow amount, after assignment.

## Ola Seeder Defaults

Seeder: `database/seeders/OlaHealthNetworkSeeder.php`

Ola flows:
- `ola_health_async_review`
- `ola_health_video_consultation`

Fee env keys:
- `OLA_HEALTH_ASYNC_NETWORK_FEE_AMOUNT`
- `OLA_HEALTH_ASYNC_PATIENT_FEE_AMOUNT`
- `OLA_HEALTH_VIDEO_NETWORK_FEE_AMOUNT`
- `OLA_HEALTH_VIDEO_PATIENT_FEE_AMOUNT`

If env values are missing, seeded fees default to `0.00`.

Video states from seeder:

```text
AR, KS, MO, MS, ND, NM, OR, WV, DC
```

All other seeded US states use async.

Seeded product mappings currently cover:
- `b12-injection`
- `glutathione`
- `nad-therapy`
- `tirzepatide`
- `semaglutide`

Follow-up mappings are currently disabled in the seeder.

## Endpoint Catalog

### Networks

`GET /dr-networks`

Query:
- `per_page`: integer 1-100

Response:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Ola Health",
      "slug": "ola-health",
      "adapter_key": "ola_health",
      "integration_mode": "api",
      "status": "active",
      "settings": {},
      "metadata": {},
      "feature_flags": {},
      "config_version": 1,
      "flow_definitions_count": 2,
      "mappings_count": 51,
      "product_mappings_count": 10
    }
  ]
}
```

`POST /dr-networks`

Body:

```json
{
  "name": "Ola Health",
  "slug": "ola-health",
  "adapter_key": "ola_health",
  "integration_mode": "api",
  "status": "inactive",
  "is_default": false,
  "settings": {},
  "metadata": {},
  "feature_flags": {}
}
```

Rules:
- `name`: required string
- `slug`: required alpha-dash unique
- `adapter_key`: required alpha-dash unique
- `integration_mode`: `api` or `manual`
- `status`: `active`, `inactive`, `paused`

`GET /dr-networks/{network}`

Response includes network plus masked credentials:

```json
{
  "id": 1,
  "name": "Ola Health",
  "credentials": [
    {
      "key": "auth_token",
      "is_secret": true,
      "configured": true,
      "fingerprint": "abc123def456",
      "value": null
    },
    {
      "key": "tenant",
      "is_secret": false,
      "configured": true,
      "value": "fitbyshot"
    }
  ]
}
```

`PATCH /dr-networks/{network}`

Body may include:

```json
{
  "name": "Ola Health",
  "status": "active",
  "integration_mode": "api",
  "is_default": false,
  "settings": {},
  "metadata": {},
  "feature_flags": {}
}
```

`DELETE /dr-networks/{network}`

Blocked if active state or product mappings exist.

`POST /dr-networks/{network}/toggle`

Enable or disable a Dr Network without sending the full network update payload.

Body optional:

```json
{
  "enabled": true
}
```

Rules:
- `enabled`: optional boolean.
- If `enabled` is omitted, API toggles current status.
- `true` sets status to `active`.
- `false` sets status to `inactive`.
- If the current status is `paused` and `enabled` is omitted, it becomes `active`.

Response:

```json
{
  "id": 1,
  "status": "active",
  "enabled": true,
  "config_version": 3
}
```

### Credentials

`GET /dr-networks/{network}/credentials`

Returns masked credential state. Never raw secrets.

`PUT /dr-networks/{network}/credentials`

Body:

```json
{
  "auth_token": "write-only",
  "secret_token": "write-only",
  "tenant": "tenant-code",
  "api_base_url": "https://dev-api.ola-digital-int.com",
  "webhook_endpoint_token": "write-only",
  "webhook_signatures_enabled": false
}
```

All fields are optional. Submit only changed values.

`POST /dr-networks/{network}/credentials/test`

Response success:

```json
{
  "ok": true,
  "adapter_key": "ola_health",
  "base_url": "https://dev-api.ola-digital-int.com",
  "tenant_present": true,
  "access_token_fingerprint": "abc123def456"
}
```

Response failure: HTTP 422.

### Flows

`GET /dr-networks/{network}/flows`

`POST /dr-networks/{network}/flows`

Body:

```json
{
  "flow_key": "ola_health_async_review",
  "name": "Ola Health Async Consultation Review",
  "description": "Patient submits intake and waits for async review.",
  "network_fee_amount": 25.0,
  "patient_fee_amount": 35.0,
  "steps": [
    {
      "step_key": "document_upload",
      "name": "Upload Documents",
      "description": "Upload government-issued ID.",
      "required": true,
      "order": 1
    }
  ],
  "is_active": true
}
```

Rules:
- `flow_key`: required on create, alpha-dash, unique per network
- `network_fee_amount`: optional numeric min 0
- `patient_fee_amount`: optional numeric min 0
- `steps.*.step_key`: one of known flow steps
- `slot_selection` cannot appear before `intake_questions`
- `provider_review` and `video_consultation` cannot appear before `review_and_submit`

Known step keys:

```text
checkout
awaiting_payment_confirmation
document_upload
intake
intake_questions
slot_selection
review_and_submit
provider_review
video_consultation
```

Other flow endpoints:
- `GET /flows/{flow}`
- `PATCH /flows/{flow}`
- `DELETE /flows/{flow}` blocked if active mappings exist
- `POST /flows/{flow}/validate`
- `POST /flows/{flow}/clone` body `{ "flow_key": "new_key", "name": "Optional name" }`
- `GET /dr-networks/{network}/flows/{flow}/content-coverage`

`GET /dr-networks/{network}/flows/{flow}/content-coverage`

Use this for the Flow Steps Coverage screen. It checks active product mappings for this network + flow, then reports which products have product-specific published question sets and active document rules.

Query: none.

Response:

```json
{
  "network": {
    "id": 1,
    "name": "Ola Health",
    "slug": "ola-health"
  },
  "flow": {
    "id": 1,
    "flow_key": "ola_health_async_review",
    "name": "Ola Health Async Consultation Review",
    "steps": []
  },
  "products_total": 5,
  "products": [
    {
      "product_id": "uuid",
      "product_slug": "semaglutide",
      "product_name": "Semaglutide"
    }
  ],
  "steps": {
    "intake_questions": {
      "step_enabled": true,
      "has_default_set": false,
      "default_set_count": 0,
      "state_specific_default_set_count": 0,
      "default_set": null,
      "products_total": 5,
      "products_with_override": 4,
      "products_using_default": 0,
      "products_without_content": 1,
      "missing": [
        {
          "product_id": "uuid",
          "product_slug": "b12-injection",
          "product_name": "B12 Injection"
        }
      ],
      "using_default": [],
      "without_content": [
        {
          "product_id": "uuid",
          "product_slug": "b12-injection",
          "product_name": "B12 Injection"
        }
      ]
    },
    "document_upload": {
      "step_enabled": true,
      "has_default_rules": true,
      "has_default_set": true,
      "default_rule_count": 3,
      "state_specific_default_rule_count": 0,
      "products_total": 5,
      "products_with_override": 5,
      "products_using_default": 0,
      "products_without_content": 0,
      "missing": [],
      "using_default": [],
      "without_content": []
    }
  }
}
```

Frontend display rules:
- `missing`: products without a product-specific override.
- `using_default`: products with no override but all-state fallback content exists.
- `without_content`: products with no override and no default content; show these as blocking risk.
- `state_specific_default_set_count` and `state_specific_default_rule_count`: state defaults exist, but they do not count as all-state fallback coverage.
- `step_enabled=false`: the flow does not include that content-bearing step, so show coverage as informational only.

### States and State Mappings

`GET /states`

Query:
- `country_code`: optional, defaults visually to US in frontend.

`POST /states`

Body:

```json
{
  "country_code": "US",
  "state_code": "CA",
  "state_name": "California",
  "is_active": true
}
```

`GET /dr-networks/state-mappings`

Query:
- `state_code`
- `network_id`
- `flow_id`
- `per_page`

`POST /dr-networks/state-mappings`

Body:

```json
{
  "state_id": 5,
  "dr_network_id": 1,
  "flow_id": 10,
  "priority": 1,
  "is_active": true
}
```

Rules:
- `flow_id` must belong to `dr_network_id`
- no `requires_video_consultation`; choose the video flow instead.

Other state mapping endpoints:
- `PATCH /dr-networks/state-mappings/{mapping}`
- `DELETE /dr-networks/state-mappings/{mapping}`
- `POST /dr-networks/state-mappings/{mapping}/toggle`
- `GET /dr-networks/state-mappings/coverage-check?network_id=1`

Coverage response:

```json
{
  "network_id": 1,
  "total_states": 51,
  "covered_states": 51,
  "unmapped_count": 0,
  "unmapped_states": []
}
```

### Product Mappings

`GET /dr-networks/{network}/product-mappings`

Query:
- `product_id`
- `flow_id`
- `per_page`

`GET /dr-networks/{network}/product-mappings/matrix`

Use this as the main product wiring UI.

Each mapped cell should show edit, toggle, delete, and configure actions. Configure routes to Product Mapping Detail with `{networkId, productId, flowId}`. Disable Configure for unmapped cells because questions/documents should only be configured once a product has a service mapping for that network + flow.

Response:

```json
{
  "network": {},
  "flows": [
    { "id": 1, "flow_key": "ola_health_async_review", "patient_fee_amount": "35.00" }
  ],
  "rows": [
    {
      "product_id": "uuid",
      "product_name": "Semaglutide",
      "product_slug": "semaglutide",
      "cells": {
        "ola_health_async_review": {
          "mapping_id": 1,
          "flow_id": 1,
          "external_service_id": "1659",
          "external_service_key": "fitbyshot-semaglutide-injection",
          "external_config": {
            "service_name": "Semaglutide Injection",
            "session_type": "initial",
            "protocol": "glp1_initial"
          },
          "is_active": true
        }
      }
    }
  ]
}
```

`POST /dr-networks/{network}/product-mappings`

Body:

```json
{
  "product_id": "uuid",
  "flow_id": 1,
  "external_service_id": "1659",
  "external_service_key": "fitbyshot-semaglutide-injection",
  "external_config": {
    "service_name": "Semaglutide Injection",
    "session_type": "initial",
    "protocol": "glp1_initial"
  },
  "is_active": true
}
```

Other endpoints:
- `PATCH /dr-networks/product-mappings/{mapping}`
- `DELETE /dr-networks/product-mappings/{mapping}`
- `POST /dr-networks/product-mappings/{mapping}/toggle`

Product Mapping Detail API usage:
- Service Mapping tab:
  - Load mappings with `GET /dr-networks/{network}/product-mappings?product_id={productId}&flow_id={flowId}`.
  - Save with `PATCH /dr-networks/product-mappings/{mapping}` or create with `POST /dr-networks/{network}/product-mappings`.
- Questions tab:
  - Load product-specific sets with `GET /dr-networks/{network}/question-sets?flow_id={flowId}&product_id={productId}`.
  - Create overrides with `POST /dr-networks/{network}/question-sets` using `flow_id` and `product_id`.
  - If no product-specific set exists, show that this product is using the flow default only if `/content-coverage` reports `has_default_set=true`.
- Documents tab:
  - Resolve `flow_key` from the selected flow.
  - Load product-specific rules with `GET /dr-networks/{network}/document-rules?flow_key={flowKey}&product_code={productSlug}`.
  - Create overrides with `POST /dr-networks/{network}/document-rules` using `flow_key` and `product_code`.
  - Include `state_code` when creating state-specific document requirements.

### Question Sets

`GET /dr-networks/{network}/question-sets`

Query:
- `flow_id`
- `product_id`
- `product_code`
- `status`: `draft`, `published`, `archived`
- `per_page`

`POST /dr-networks/{network}/question-sets`

Body:

```json
{
  "flow_id": 1,
  "product_id": "uuid",
  "product_code": "semaglutide",
  "state_code": "*",
  "set_key": "ola_semaglutide_async_initial",
  "set_name": "Semaglutide Async Initial",
  "metadata": {
    "protocol": "glp1_initial"
  }
}
```

If `product_id` is sent, API uses product slug as `product_code`.

Use `state_code: "*"` for the all-state default. Use a concrete state code such as `CA` only when the question set should override the product/flow set for that state. Runtime resolution supports this and picks the most specific published set.

Other set endpoints:
- `GET /question-sets/{set}`
- `PATCH /question-sets/{set}`
- `POST /question-sets/{set}/validate`
- `POST /question-sets/{set}/publish`
- `POST /question-sets/{set}/archive`
- `POST /question-sets/{set}/clone`
- `POST /question-sets/{set}/preview`

Publish/validate blocks:
- no active questions
- duplicate question keys
- conditional question without conditions
- conditions referencing missing or inactive `answers.{question_key}`
- invalid condition operators
- invalid blocking-rule hard stop types
- blocking rules without conditions

Allowed condition operators:

```text
equals
not_equals
in
not_in
exists
missing
greater_than
less_than
```

Allowed hard stop types:

```text
refer_out
provider_review_required
```

Preview body:

```json
{
  "patient": {
    "gender": "female",
    "age": 35
  },
  "prior_answers": {
    "glp1_recent_use": "yes"
  }
}
```

### Questions

`GET /question-sets/{set}/questions`

`POST /question-sets/{set}/questions`

Body:

```json
{
  "question_key": "glp1_recent_use",
  "question_text": "Have you used GLP-1 medication recently?",
  "help_text": null,
  "sort_order": 10,
  "input_type": "radio",
  "options": [
    { "id": "yes", "label": "Yes", "value": "yes" },
    { "id": "no", "label": "No", "value": "no" }
  ],
  "is_required": true,
  "is_conditional": false,
  "condition_rules": null,
  "network_validation": {
    "blocking_rules": [
      {
        "rule_key": "glp1_recent_use_no",
        "reason": "example_reason",
        "hard_stop_type": "refer_out",
        "message": "Example message.",
        "conditions": [
          { "source": "answers.glp1_recent_use", "operator": "equals", "value": "no" }
        ]
      }
    ]
  },
  "metadata": {},
  "is_active": true
}
```

Input types:

```text
text
long_text
number
select
multiselect
radio
checkbox
boolean
date
file
nested
```

System-filled questions:
- Use `metadata.frontend_hidden: true` plus `metadata.auto_fill`.
- Supported `auto_fill` values: `current_date`, `patient_name`, `order_uuid`, `calculated_bmi`.
- These questions are not returned in the patient-facing intake question payload.
- The frontend must not submit answers for them; the backend writes them during intake answer submission.
- Current Ola GLP-1 seeder uses this for `glp1_signature_date`, which is auto-filled as the server's current date.
- Current Ola GLP-1 seeder uses `calculated_bmi` for `glp1_bmi`. Frontend should render `glp1_height_feet` and `glp1_height_inches` as one grouped Height UI, render `glp1_weight_lbs` normally, and never render or submit `glp1_bmi`.

Other endpoints:
- `PATCH /questions/{question}`
- `DELETE /questions/{question}` deactivates only
- `POST /questions/{question}/reorder` body `{ "new_sort_order": 20 }`
- `POST /question-sets/{set}/reorder-bulk` body `{ "orders": { "123": 10, "124": 20 } }`
- `POST /questions/{question}/test-blocking-rule`

Blocking rule test body:

```json
{
  "answer_value": "yes",
  "patient": {
    "gender": "female"
  },
  "prior_answers": {}
}
```

Response:

```json
{
  "would_trigger": true,
  "triggered_rules": [
    {
      "rule_key": "example",
      "reason": "example_reason",
      "message": "Example message",
      "hard_stop_type": "refer_out",
      "substance": null,
      "conditions": []
    }
  ]
}
```

### Document Rules

`GET /dr-networks/{network}/document-rules`

Query:
- `flow_key`
- `flow_id`
- `state_code`
- `product_code`
- `per_page`

`POST /dr-networks/{network}/document-rules`

Body:

```json
{
  "flow_key": "ola_health_async_review",
  "state_code": null,
  "product_code": null,
  "rule_key": "ola_async_identity",
  "rule_name": "Ola Async Identity",
  "priority": 1,
  "requirement_type": "identity",
  "operator": "any",
  "document_ids": [1, 2],
  "is_required": true,
  "conditions": [],
  "error_message": "Government ID is required.",
  "help_text": "Upload passport or driver license.",
  "is_active": true
}
```

Operators:
- `any`
- `all`
- `exact`

Requirement types:
- `identity`
- `verification`
- `medical`
- `condition_specific`
- `insurance`
- `consent`
- `prescription`

Other endpoints:
- `PATCH /document-rules/{rule}`
- `DELETE /document-rules/{rule}` deactivates only
- `POST /document-rules/{rule}/preview`

Preview body:

```json
{
  "uploaded_document_type_ids": [1]
}
```

### Document Types

`GET /document-types`

`POST /document-types`

Body:

```json
{
  "key": "lab_requisition",
  "name": "Lab Requisition",
  "category": "medical",
  "description": "Lab order or requisition.",
  "metadata": {},
  "is_active": true
}
```

Categories:

```text
identity
verification
medical
insurance
consent
prescription
```

### Webhook Config and Logs

`GET /dr-networks/{network}/webhook-config`

`PATCH /dr-networks/{network}/webhook-config`

Body:

```json
{
  "webhook_endpoint_token": "write-only",
  "webhook_signatures_enabled": false
}
```

`GET /dr-networks/{network}/webhook-log`

Query:
- `status`: `pending`, `processing`, `processed`, `failed`
- `per_page`

`POST /dr-networks/{network}/webhook-log/{event}/replay`

Queues the stored webhook event for processing again.

### Cases

`GET /dr-networks/{network}/cases`

Use this as the main Dr Network cases/orders table. It returns compact rows for the list page only.

Query:
- `search`: order UUID, numeric order ID, patient name/email/phone, or network case ID
- `status`: order status
- `payment_status`: order payment status
- `flow_status`: `pending`, `running`, `paused`, `completed`, `failed`, `cancelled`
- `current_step_key`: flow step key such as `intake_questions` or `provider_review`
- `state_code`: state code such as `CA`
- `product_id`: product UUID
- `date_from`: order created-at lower bound
- `date_to`: order created-at upper bound
- `per_page`: integer 1-100

Response is paginated:

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 123,
      "order_id": 123,
      "order_uuid": "9d7d2f3c-0e89-4dd8-8c1d-b7c22f2c3a10",
      "state_code": "CA",
	      "status": "submitted",
	      "payment_status": "paid",
	      "final_amount": "235.00",
	      "total_paid_amount": "235.00",
	      "dr_network_patient_fee_amount": "35.00",
	      "dr_network_fee_amount": "25.00",
	      "created_at": "2026-07-09T11:55:00.000000Z",
	      "patient": {
	        "id": 55,
	        "name": "Jane Doe",
	        "email": "jane@example.com",
	        "phone": "+15555555555"
	      },
	      "product": {
	        "id": "uuid",
	        "name": "Semaglutide",
	        "slug": "semaglutide"
	      },
	      "flow": {
	        "id": 10,
	        "flow_key": "ola_health_async_review",
	        "name": "Ola Health Async Consultation Review"
      },
      "flow_run": {
	        "id": 44,
	        "status": "running",
	        "current_step_key": "provider_review",
	        "status_reason": null
	      },
	      "network_case_id": "ola-case-123",
	      "consultation_status": "submitted",
	      "finance_transaction_status": "active"
	    }
	  ],
	  "per_page": 25,
  "total": 1
}
```

`GET /dr-networks/{network}/cases/{order}`

Use this for the separate case detail page after clicking a row in the cases list. `{order}` is the internal numeric `orders.id`, not `order_uuid`.

The detail response includes the full case payload:
- full patient contact/profile subset
- order amounts and fee snapshots
- Dr Network identity
- product and flow, including all configured flow steps annotated with run progress
- full current flow run with context and step history
- consultation record including network metadata
- finance transaction
- payments
- intake answers
- uploaded documents

Open a case from the list with:

```http
GET /api/v1/admin/dr-networks/{networkId}/cases/{order_id}
```

Recommended patient progress display:
- Use `flow.current_step` for the highlighted current step.
- Use `flow.steps` for the full progress tracker, because it includes every configured flow definition step, even if the runtime has not created a `flow_run_step` row for it yet.
- Use `flow_run.steps` only as the raw runtime step history/debug view.

Flow detail shape:

```json
{
  "flow": {
    "id": 10,
    "flow_key": "ola_health_async_review",
    "name": "Ola Health Async Consultation Review",
    "current_step": {
      "step_key": "provider_review",
      "name": "Provider Review",
      "description": null,
      "required": true,
      "order": 5,
      "is_current": true,
      "run_step_id": 12,
      "status": "in_progress",
      "error_message": null,
      "started_at": "2026-07-09T12:10:00.000000Z",
      "completed_at": null
    },
    "steps": [
      {
        "step_key": "document_upload",
        "name": "Upload Documents",
        "description": "Upload government-issued ID.",
        "required": true,
        "order": 1,
        "is_current": false,
        "run_step_id": 10,
        "status": "completed",
        "error_message": null,
        "started_at": "2026-07-09T12:01:00.000000Z",
        "completed_at": "2026-07-09T12:03:00.000000Z"
      },
      {
        "step_key": "provider_review",
        "name": "Provider Review",
        "description": null,
        "required": true,
        "order": 5,
        "is_current": true,
        "run_step_id": 12,
        "status": "in_progress",
        "error_message": null,
        "started_at": "2026-07-09T12:10:00.000000Z",
        "completed_at": null
      },
      {
        "step_key": "video_consultation",
        "name": "Video Consultation",
        "description": null,
        "required": true,
        "order": 6,
        "is_current": false,
        "run_step_id": null,
        "status": "pending",
        "error_message": null,
        "started_at": null,
        "completed_at": null
      }
    ]
  }
}
```

### Flow Runs

`GET /dr-networks/{network}/flow-runs`

Query:
- `status`: `pending`, `running`, `paused`, `completed`, `failed`, `cancelled`
- `stuck_since`: timestamp
- `per_page`

`GET /dr-networks/{network}/flow-runs/{run}`

`POST /dr-networks/{network}/flow-runs/{run}/retry-poll`

Immediately calls the adapter status endpoint for that run's `network_case_id`.

### Finance

Finance is table-backed. Do not treat cache values as authoritative for money.
There is no `dr_network_finance_totals` table. Dashboard totals are always calculated from source rows in `dr_network_transactions` and `dr_network_payouts`.

Transactions are created automatically when a consultation case is submitted to the network, after `ConsultationRecord` is created. Amounts come from the order snapshots:
- `patient_paid_amount` = `orders.dr_network_patient_fee_amount`
- `network_owed_amount` = `orders.dr_network_fee_amount`

`GET /dr-networks/{network}/finance/summary`

Query:
- `date_from`: optional date/timestamp
- `date_to`: optional date/timestamp

Response:

```json
{
  "total_patient_paid": "350.00",
  "total_network_owed": "250.00",
  "profit": "100.00",
  "total_paid_out": "200.00",
  "remaining_balance": "50.00",
  "transaction_count": 10,
  "payout_count": 2
}
```

`total_patient_paid`, `total_network_owed`, `profit`, `total_paid_out`, `transaction_count`, and `payout_count` respect the date filter. `remaining_balance` is always all-time: all active network owed minus all completed payouts.

`GET /dr-networks/{network}/finance/transactions`

Query:
- `status`: `active`, `void`, `refunded`
- `date_from`: optional date/timestamp
- `date_to`: optional date/timestamp
- `per_page`: integer 1-100

Each transaction includes:
- order: `id`, `order_uuid`, `state_code`, product
- consultation record: `network_case_id`, `network_status`, `internal_status`, `submitted_at`
- flow: `id`, `flow_key`, `name`
- money fields: `patient_paid_amount`, `network_owed_amount`, computed `profit_amount`

`POST /dr-networks/{network}/finance/transactions/{transaction}/void`

Body:

```json
{
  "reason": "Refunded before network invoice was paid."
}
```

Only `admin` and `super_admin` can void. Only active transactions can be voided.

`GET /dr-networks/{network}/finance/payouts`

Query:
- `status`: `pending`, `completed`, `cancelled`
- `date_from`: optional date/timestamp
- `date_to`: optional date/timestamp
- `per_page`: integer 1-100

`POST /dr-networks/{network}/finance/payouts`

Body:

```json
{
  "amount": 250.00,
  "currency": "USD",
  "method": "bank_transfer",
  "reference_number": "OLA-INV-2026-001",
  "note": "July invoice payment",
  "status": "completed",
  "paid_at": "2026-07-08T12:00:00Z"
}
```

Rules:
- `amount`: required numeric min `0.01`
- `currency`: optional 3-character code, defaults to `USD`
- `method`: `bank_transfer`, `wire`, `check`, `other`
- `status`: `pending`, `completed`, `cancelled`, defaults to `completed`
- `paid_at`: optional date, defaults to now

Only `admin` and `super_admin` can record payouts.

## Frontend Error Handling

Use the API's HTTP status:
- `401`: login required
- `403`: role not allowed
- `422`: validation/business rule failure
- `404`: missing resource

For validation responses, show `message` plus `errors`. For question set validate/publish, render every error as a blocking checklist before allowing publish.

For Dr Network failed flows:
- Display `status_message` or `failure_message` to admins/patients.
- Treat `failure_reason` as a machine code for filtering/debugging only.
- Example: display `Based on BMI, this treatment is not clinically appropriate through this telehealth flow.`, not `bmi_not_eligible`.
- Step rows expose `error_message` for display and `error_code` for machine/debug use.

## Suggested UI Components

- Network list table with status badge.
- Flow editor with:
  - step picker/reorder list
  - `network_fee_amount`
  - `patient_fee_amount`
  - validate button
- State coverage banner from `/coverage-check`.
- Product mapping matrix with inline editable cells.
- Question editor with:
  - options editor
  - condition builder
  - blocking-rule builder
  - persistent preview panel
  - validate/publish checklist
- Webhook log table with replay action.
- Flow run monitor with retry-poll action.
- Finance page with summary cards, remaining balance panel, transactions tab, payouts tab, record payout dialog, and void transaction action.
