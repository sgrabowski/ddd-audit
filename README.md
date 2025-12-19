# Quality Audit Evaluation System

Domain-Driven Design implementation of a quality audit management system.

## About This Project

This is a recruitment assignment demonstrating domain modeling using DDD principles. The system manages quality audit evaluations for clients, handling complex business rules around evaluation lifecycle, expiration, replacement, and locking mechanisms.

The focus here is business domain logic and rich domain models, not technical infrastructure.

## Setup & Running Tests

### Prerequisites
- Docker & Docker Compose
- Make (optional, for convenience)

### Quick Start

```bash
make build
make up
# Install dependencies
make install-dev
# Run tests
make test
```

## Business Requirements

The specification defines 27 business rules for managing audit evaluations:

### Prerequisites (BR-01 to BR-08)
1. The company conducts quality audits for its Clients.
2. Each audit is carried out offline, during an on-site visit within one day.
3. The system allows the recording of evaluation reports.
4. The audit is carried out by the Supervisor.
5. The audit is carried out in the indicated Standard.
6. Clients can have multiple evaluations in different standards.
7. The Client being evaluated must have an active contract with the Supervisor.
8. The Supervisor must have authority for the standard of audit being conducted.

### Rating and Expiration (BR-09 to BR-11)
9. Evaluations can have positive or negative ratings. Rating is a part of an evaluation report.
10. The evaluation has an expiration date provided in the evaluation report. After that date is exceeded, the evaluation expires.
11. The evaluation expiration date can't be less than 180 days from the day the audit was conducted.

### Subsequent Evaluation Timing (BR-12)
12. For the same Standard, subsequent evaluation can be recorded after a period of:
    - a. not less than 180 days after positive evaluation
    - b. not less than 30 days for negative evaluation

### Replacement Logic (BR-13 to BR-14)
13. For the same Standard, subsequent evaluation with positive rating replaces the current one if prior was positive and active on the day of a subsequent evaluation. This way, Client keeps continuity for the given Standard.
14. Evaluation with negative rating can't replace prior evaluation nor can itself can't be replaced.

### Participants (BR-15 to BR-18)
15. Client is considered as Owner of evaluation.
16. Supervisor who conducted an audit is considered as Manager of evaluation.
17. Other Clients and Supervisors can be considered as Watchers of evaluation.
18. It is possible to change the Manager of the evaluation for another Supervisor. New Manager must have an active contract with the Client.

### Locking Mechanism (BR-19 to BR-27)
19. It is possible to lock the evaluation by suspension or withdrawal.
20. Suspended evaluation can be unlocked.
21. Suspended evaluation may be withdrawn.
22. Withdrawn evaluation cannot be unlocked nor it cannot be changed into suspension.
23. Expired evaluation cannot be locked.
24. Locked evaluation can still expire.
25. When evaluation is expired or replaced it will be considered inactive.
26. It is not possible to lock an evaluation that is already locked, it is necessary to unlock it in advance. Only changing Suspension into withdrawn is allowed.
27. Locking history matters.

## Understanding & Assumptions

### Domain Interpretation

**Audit vs Evaluation vs Report**:
- **Audit**: Real-world event (one-day on-site visit) - not modeled as entity
- **Evaluation**: The system record/aggregate representing audit results
- **Evaluation Report**: Value object within Evaluation containing rating, dates, and standard

### Key Assumptions (may or may not be incorrect)

1. While BR-25 only mentions "expired OR replaced", a locked evaluation is also considered inactive for replacement purposes. This conservative interpretation prevents locked evaluations from being replaced.

2. "Other Clients and Supervisors" excludes the Owner and Manager. They already have explicit roles and don't need to be watchers.

## Technical Decisions

### 1. Aggregate Boundary: QualityAudit

`QualityAudit` (identified by Client + Standard) is the aggregate root containing multiple `Evaluation` entities.

**Why**:
- BR-12 timing rules span multiple evaluations for same client+standard
- BR-13 replacement requires modifying prior evaluation when creating new one
- Avoids cross-aggregate modification
- Single transaction boundary for all related evaluations

**Alternative**: Each Evaluation as separate aggregate would require cross-aggregate modification during replacement

### 2. Clock Abstraction

`Clock` interface with `SystemClock` (production) and `FixedClock` (testing).

**Why**:
- Many business rules are time-dependent (expiration, timing constraints)
- Makes tests deterministic and easy to write
- Explicit dependency is clearer than hidden `new DateTimeImmutable()`

### 3. Locking State Model

Model as nullable value objects (`Suspension`, `Withdrawal`), not as enum states.

**Why**:
- "Unlocked" is absence of lock, not a separate state
- Value objects capture timestamps (when suspended/withdrawn)
- More expressive and intention-revealing
- Matches domain language better

**Alternative**: State enum would be simpler but less expressive and wouldn't capture timestamps.

### 4. Event Architecture for BR-27

Domain events (`EvaluationSuspended`, `EvaluationUnlocked`, `EvaluationWithdrawn`) with simple projection for history.

**Alternative**: Simple array of history entries would also work, but pollute the aggregate

### 5. Service Responsibilities

**Decision**: Two domain services - `AuditRecorder` (creates) and `AuditManager` (manages participants).

**Why**:
- Clear separation: creation vs management
- Services stay thin, business logic in aggregates
- Cross-aggregate validation (contracts, authorities) needs services

## Ambiguities & Questions

If this were a real client project, I would clarify the following:

### 1. Contract Lifecycle
- What happens to evaluations if contract becomes inactive after evaluation is created?
- Can evaluations still be locked/managed if contract expires?

### 2. Authority Revocation
- What happens if supervisor's authority is revoked after conducting audits?

### 3. Locking Business Purpose
- What is the business reason for suspension vs withdrawal?
- Who can lock evaluations and under what circumstances?
- Is there a workflow for resolution?

### 4. Historical Audits
- Can audits from the past be entered retroactively?
- What validation should apply to audit dates?

### 5. Activity Definition Clarification
- BR-25 says "inactive = expired OR replaced"
- Should locked evaluations also be considered inactive? (current implementation)
- This affects whether locked evaluations can be replaced

### 6. Manager Change Restrictions
- Can manager be changed on expired evaluations?
- Can manager be changed on replaced evaluations?
