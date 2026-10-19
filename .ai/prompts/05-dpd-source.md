# Task 05 — Assess the DPD source before implementing an adapter

Inspect current official DPD Hungary integration documentation and identify the intended
pickup-point source, credentials, usage terms, point identity, country support, types,
availability, COD features and refresh limits. Record direct links and verification dates.

The WooCommerce reference uses points-api.kvikk.hu. Do not assume this is an official DPD
source or that its access is available for unrelated packages. Do not add it as a hidden
dependency. Compare direct and explicitly contracted sources; document unresolved questions.

Deliver a source ADR and implementation-ready fixture/normalization plan. Do not claim DPD
support or implement against an unverified endpoint. After source selection, add an adapter
through existing contracts with isolated synthetic tests and failure-preservation coverage.
