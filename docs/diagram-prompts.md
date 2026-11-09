# Chapter 4 Diagram Prompts — Polymedic Clinic System

Every prompt below was checked against the actual code (`app/Controllers`, `app/Models`, `app/Config/Routes.php`) and the live `polymedic_db` schema on 2026-11-09.

**How to use:** paste one prompt at a time into your diagram tool (ChatGPT, Gemini, Claude, Eraser, Whimsical, Lucid AI, or a Mermaid/draw.io generator). To get editable output, add one line at the end, such as *"Output as Mermaid code"* or *"Output as draw.io XML"*.

**Notation used in all flowcharts:**
- Oval = Start/End
- Parallelogram = input/output
- Rectangle = process
- Diamond = decision (Yes/No labels)
- Cylinder = database table
- Circle = on-page connector
- Pentagon = off-page connector

---

## Figure 4.1 — Login and Access Flowchart

**What was wrong or missing in the current figure**
- It lacks the empty-field check and the "sign-in temporarily unavailable" path for database errors.
- Admins may sign in from **any** role option. Only non-admins must match the selected role.
- The session ID is regenerated before the session data is stored.
- An old MD5 password is upgraded to a secure hash on successful login.
- Part (b): a user who isn't logged in goes to the **login page**, not to a dashboard. Admins are **not** allowed into other roles' pages.

### Prompt 4.1(a)

```
Draw a top-to-bottom flowchart titled "Figure 4.1(a) Sign-in Sequence" using standard flowchart symbols (oval start/end, parallelogram input/output, rectangle process, diamond decision with Yes/No labels, cylinder for database). Keep it on one page, left-aligned main path, error branches going right and looping back to the login form.

Steps:
1. Start (oval): "User opens Login page"
2. Input (parallelogram): "Enter username, password, and select role (Administrator, Receptionist, Medical Technologist, Radiologist)"
3. Decision: "Username and password filled in?" — No → Output "Username and password are required" → back to step 2.
4. Process: "Look up user by username" (connect to cylinder "users").
5. Decision: "Database error?" — Yes → Output "Sign-in is temporarily unavailable" → back to step 2.
6. Decision: "User found AND password matches (bcrypt, or legacy MD5)?" — No → Output "Invalid username or password" → back to step 2.
7. Process (only on legacy MD5 match, small side box): "Re-hash password with bcrypt and save".
8. Decision: "Account status = active?" — No → Output "Your account is inactive. Please contact administrator." → back to step 2.
9. Decision: "Account role = admin?" — Yes → go to step 11. No → step 10.
10. Decision: "Account role matches selected role?" — No → Output "The selected role does not match this account" → back to step 2.
11. Process: "Regenerate session ID and store user id, username, full name, email, role, avatar in session".
12. Process: "Record last_login_at" (cylinder "users").
13. Decision: "Role has a dashboard?" 
    - admin → End "Admin Dashboard"
    - receptionist → End "Receptionist Dashboard"
    - med_tech → End "Med Tech Dashboard"
    - radiologist → End "Radiologist Dashboard"
    - none (e.g. physician) → Process "Destroy session" → Output "Your account has no dashboard assigned" → End "Back to Login".
```

### Prompt 4.1(b)

```
Draw a small flowchart titled "Figure 4.1(b) Access Check on Every Protected Page" with standard symbols.

1. Start: "User requests a protected page (admin/*, receptionist/*, medtech/*, radiologist/*)"
2. Decision: "Logged in?" — No → Output "Please login to access this page." → End "Redirect to Login page".
3. Decision: "User's role is allowed on this page group?" — Yes → End "Show requested page".
4. No → Output "Access denied. You do not have permission to open that page." → End "Redirect to the user's own dashboard".

Add a note box beside step 3: "Each page group allows exactly one role. Administrators are NOT allowed into Receptionist, Med Tech, or Radiologist pages."
```

---

## Figure 4.2 — Online Appointment Booking Flowchart

**What was wrong or missing in the current figure**
- The slot check covers working day, booking window, valid slot time, past time today, blocked slot and capacity (default 1 per department). The caption simplified this.
- Patient matching uses the **same name** first, then age + sex, then age only, then email or phone.
- **Override booking is Receptionist-only.** It skips the slot-capacity check but **not** the closed-day or past-date check. It saves directly as **approved**, not pending.
- Approve, cancel and complete each have their own status rule (details in the prompt). **Delete is Admin-only.**

### Prompt 4.2(a)

```
Draw a top-to-bottom flowchart titled "Figure 4.2(a) Online Booking (Public Visitor)" with standard flowchart symbols. Use cylinders for tables: clinic_settings, availability_slots, appointments, patients, notifications.

1. Start: "Visitor opens Book Appointment page"
2. Input: "Select laboratory and/or X-ray services"
3. Input: "Pick date" → Process "Load time slots for that date" (reads clinic_settings, availability_slots, appointments). Decision "Date within booking window and clinic open?" — No → Output "Clinic closed / outside booking window" → back to pick date.
4. Input: "Pick an available time; enter full name, age, sex, email, phone, other requests"
5. Process: "Submit form"
6. Decision: "Form valid? (name 2–150 chars, age 1–130, sex Male/Female/Other, valid email, phone 10–20 digits, notes ≤ 1,000 chars)" — No → Output "Show field errors" → back to step 4.
7. Decision: "At least one laboratory or X-ray service selected?" — No → Output "Please select at least one service" → back to step 2.
8. Process: "Set service type = laboratory, xray, or both"
9. Decision: "Slot bookable? (working day, not a holiday, within booking window, valid slot time, not already past today, slot not blocked, bookings < capacity for each department)" — No → Output "That time slot just became unavailable" → back to step 4.
10. Process: "Begin transaction and lock appointments at that date and time (SELECT … FOR UPDATE)"
11. Decision: "Slot still bookable? (re-check inside the lock)" — No → Process "Rollback" → Output "Slot unavailable" → back to step 4.
12. Process: "Save appointment, status = pending, reference APPT-YYYYMMDD-XXXXXXXX" (cylinder appointments)
13. Process: "Find or create patient: same name → then same age + sex → then same age → then same email or phone; if none, create new patient code PAT-YYYY-…" (cylinder patients)
14. Process: "Commit transaction". Decision "Commit OK?" — No → Output "Failed to save appointment" → back to step 4.
15. Process: "Notify Administrator and Receptionist: New Appointment Request" (cylinder notifications)
16. Output: "Booking confirmation page with reference number — 'Waiting for clinic confirmation'"
17. End.
```

### Prompt 4.2(b)

```
Draw a flowchart titled "Figure 4.2(b) Appointment Review, Approval and Override (Front Desk)" with standard symbols. Use three columns for the three actions (Approve / Cancel / Complete) under one shared start, plus a separate lane for Override Booking.

Main path:
1. Start: "Receptionist or Administrator opens an appointment"
2. Decision: "Appointment found?" — No → Output "Appointment not found" → End.
3. Decision: "Action chosen?" → three branches:

APPROVE branch:
 A1. Decision "Status is completed, cancelled or no_show?" — Yes → Output "This appointment cannot be approved" → End.
 A2. Process "Set status = approved, arrival_time = now" (appointments)
 A3. Process "Find or create patient record" (patients)
 A4. Loop for each department (Laboratory, X-ray): Decision "Appointment has services for this department?" — No → skip. Decision "Request already exists for this appointment and department?" — Yes → note "request already exists", skip. Otherwise Process "Create diagnostic request, status = pending, reference LAB-YY-XXXXXX or XR-YY-XXXXXX" (diagnostic_requests) → Process "Notify Admin and the department (Med Techs or Radiologists)" (notifications).
 A5. Output "Appointment approved + patient code" → End.

CANCEL branch:
 C1. Decision "Status = completed?" — Yes → Output "Completed appointments cannot be cancelled" → End.
 C2. Process "Set status = cancelled" → End.

COMPLETE branch:
 K1. Decision "Status = approved?" — No → Output "Only approved appointments can be marked as completed" → End.
 K2. Process "Set status = completed" → End.

Side note box: "Delete appointment — Administrator only."

OVERRIDE BOOKING lane (Receptionist only):
 O1. Start "Receptionist chooses Override booking (slot is full)"
 O2. Input "Date, time, patient details, service type, services, reason for override"
 O3. Decision "Valid? (name, age 0–130, sex, service type, reason 5–255 chars)" — No → error → back to O2.
 O4. Decision "Date in the past?" — Yes → Output "Override bookings cannot be made for a past date" → back.
 O5. Decision "Clinic open that day?" — No → Output "Overrides skip a full slot, not a closed day" → back.
 O6. Decision "Required services picked? (for Both: at least one lab AND one X-ray)" — No → error → back.
 O7. Process "Save appointment: status = approved, is_override = 1, override_reason, override_by = staff name, arrival_time = now if today" (appointments). Note: slot capacity is NOT checked.
 O8. Process "Find or create patient" → Process "Create diagnostic requests per department + notify department (titled 'Front Desk')"
 O9. End "Override booking created".
```

---

## Figure 4.3 — Walk-in Registration Flowchart

**What was wrong or missing in the current figure**
- Patient matching order was wrong. It is **same name → age + sex → age → email or phone**, not "email first".
- Walk-in sex is **Male or Female only** (online booking also allows Other).
- The flow is missing the request-type choice (Laboratory / X-ray / Lab + X-ray) and the per-department referring doctor.
- If no patient code can be assigned, the flow stops with an error.
- Both **Receptionist and Administrator** can register walk-ins.

### Prompt 4.3

```
Draw a top-to-bottom flowchart titled "Figure 4.3 Walk-in Registration" with standard flowchart symbols and cylinders for patients, diagnostic_requests, notifications.

1. Start: "Receptionist or Administrator opens New Walk-in Request"
2. Input: "Request type (Laboratory, X-ray, or Lab + X-ray); patient name, age, sex, email (optional), phone (optional); priority (Routine or STAT); referring doctor per department; services per department"
3. Decision: "Valid? (request type chosen, name filled, age 0–130, sex Male or Female, at least one service for each chosen department)" — No → Output "Please fix the errors below" → back to step 2.
4. Process: "Search patients by same name (case/spacing ignored)"
5. Decision: "Match found? (try in order: same age + sex → same age → same email or phone)"
   - Yes → Process "Fill in missing email/phone; set source = walk-in" (patients)
   - No → Process "Generate patient code PAT-YYYY-…; create patient, source = walk-in" (patients). Decision "Insert failed as duplicate?" — Yes → Process "Re-look up by name + age + sex, else retry with a fallback code".
6. Decision: "Patient code assigned?" — No → Output "Could not assign a patient code" → End.
7. Process: "In one transaction, create one diagnostic request per chosen department: status = pending, reference LAB-YY-XXXXXX / XR-YY-XXXXXX, priority, doctor, services" (diagnostic_requests). Decision "Insert failed?" — Yes → Rollback → Output "Could not create the request" → End.
8. Process: "Notify Administrator + Medical Technologists (lab) and/or Radiologists (X-ray)" (notifications)
9. Output: "Request(s) created — patient name and code"
10. End.
```

---

## Figure 4.4 — Start, Assignment and Payment Flowchart

**What was wrong or missing in the current figure**
- Start applies to requests that are **pending or cancelled** only.
- The per-patient lock waits up to **5 seconds**. If someone else is starting a request for that patient, the user is asked to try again.
- The "one handler" rule spans **both departments**: a patient with an active (in progress or draft) request anywhere is locked.
- Pricing adds a missing detail. A service not in the active catalogue is priced **₱0** and logged. A request with no services gets **Consultation Fee ₱500**.
- The Start payment is always recorded as **cash, full amount**.

### Prompt 4.4(a)

```
Draw a flowchart titled "Figure 4.4(a) Choosing and Assigning the Staff Member" with standard symbols, cylinders for users and diagnostic_requests, and end with off-page connector "A".

1. Start: "Receptionist or Administrator presses Start on a diagnostic request"
2. Process: "Show list of active staff of the matching role (Medical Technologists for lab, Radiologists for X-ray), with current workload" (users)
3. Input: "Choose staff member"
4. Decision: "Staff chosen?" — No → Output "Choose who will handle this request before starting it" → back to step 3.
5. Process: "Acquire per-patient lock (wait up to 5 seconds)"
6. Decision: "Lock acquired?" — No → Output "Someone else is starting a request for this patient. Please try again." → End.
7. Process: "Re-read the request" (diagnostic_requests)
8. Decision: "Status is pending or cancelled?" — No → Output "Already being handled by … / This request is already …" → release lock → End.
9. Decision: "Same patient has another active request (in progress or draft) in lab OR X-ray?" — Yes → Output "This patient is already being handled by <name> (<reference>)" → release lock → End.
10. Decision: "Chosen staff still active with the right role?" — No → Output "The selected staff member is no longer available" → release lock → back to step 3.
11. Process: "Begin transaction"
12. Process: "Set status = in_progress, assigned_to_id, assigned_to_name, assigned_at = now (only if status is still pending/cancelled)" (diagnostic_requests)
13. Decision: "Exactly one row updated?" — No → Rollback → Output "Already being handled by …" → release lock → End.
14. Off-page connector: "A"
```

### Prompt 4.4(b)

```
Draw a flowchart titled "Figure 4.4(b) Recording the Payment and Invoice" with standard symbols. Start from off-page connector "A". Cylinders: payments, services, invoices, invoice_items.

1. Connector "A"
2. Decision: "Payment already exists for this request?" (payments)
   - Yes → Process "Commit transaction" → Process "Link payment to invoice and recalculate" → go to step 9.
3. Process: "Price each service from the active services catalogue" (services). Note box: "Unknown service = ₱0 (logged); no services = Consultation Fee ₱500"
4. Process: "Insert payment: full amount, method = cash, status = paid, received by current staff" (payments)
5. Decision: "Payment saved?" — No → Process "Rollback (request stays pending)" → Output "Could not record the payment. The request was not started." → End.
6. Process: "Commit transaction"
7. Process: "Create invoice INV-YYYY-NNNNNN with line items, or link to the request's existing invoice; recalculate" (invoices, invoice_items)
8. Decision: "Invoice step failed?" — Yes → Process "Log error (payment is kept); fixed later by running: php spark invoices:backfill".
9. Process: "Release per-patient lock"
10. Output: "Assigned to <staff>. Payment recorded: ₱<amount>" — monitoring screen shows "Waiting for lab / X-ray" until the staff member opens it.
11. End.
```

---

## Figure 4.5 — Department Work and Release Flowchart

**What was wrong or missing in the current figure**
- **The receptionist is not notified when a lab result is released.** The release notification goes to the **Administrator and Medical Technologists**. The receptionist sees the "Released — ready to print" status on the monitoring screen.
- **An X-ray release sends no notification at all.**
- Every action first checks: request paid (Start done) → not assigned to another staff member.
- Lab Release requires at least one test, a result for every test, and Findings/Interpretation. Saving a draft on a completed or released lab result keeps its status.
- X-ray: up to **5 images** (JPG, PNG, GIF or WEBP). Images and the report **cannot be changed after release**.

### Prompt 4.5(a)

```
Draw a flowchart titled "Figure 4.5(a) Laboratory — Medical Technologist" with standard symbols. Cylinders: payments, diagnostic_requests, lab_results, notifications.

1. Start: "Medical Technologist opens a lab request"
2. Decision: "Request paid? (Start was pressed)" (payments) — No → Output "This request has not been paid yet. The receptionist must click Start." → End.
3. Decision: "Assigned to another Med Tech?" — Yes → Output "This request is assigned to <name>. Only they can work on it." → End.
4. Input: "Enter results per test: test name, result, unit, reference range, flag (normal/high/low/critical); Findings / Interpretation; Remarks"
5. Decision: "Button pressed?" → three branches:
   - SAVE → Process "Save results" (lab_results) → Decision "Current status in_progress?" Yes → status = draft; if completed/released → keep status; → Output "Draft saved" → End.
   - COMPLETE → Process "Save results; status = completed" → Output "Findings completed" → End.
   - RELEASE → Decision "At least one test, every test has a result, and Findings filled?" — No → Output "Please fill in all required fields before releasing" → back to step 4. Yes → Process "Replace saved results; status = released, released_at = now; stamp technologist name" (diagnostic_requests, lab_results) → Process "Notify Administrator and Medical Technologists: Laboratory Result Released" (notifications) → Output "Result released" → step 6.
6. Output: "Monitoring screen shows 'Released — ready to print'; Receptionist prints the result (or emails it as PDF)"
7. End.
```

### Prompt 4.5(b)

```
Draw a flowchart titled "Figure 4.5(b) X-ray — Radiologist" with standard symbols. Cylinders: payments, diagnostic_requests, and a data store "X-ray image files (public/uploads/xray)".

1. Start: "Radiologist opens an X-ray examination"
2. Decision: "Request paid? (Start was pressed)" — No → Output "This study has not been paid yet" → End.
3. Decision: "Assigned to another Radiologist?" — Yes → Output "Only <name> can work on it" → End.
4. Decision: "Already released?" — Yes → Output "Report already released and cannot be edited" → End (view/print only).
5. Process: "Upload X-ray images (JPG, PNG, GIF, WEBP; maximum 5 per study); remove image if needed" (image files, diagnostic_requests.image_paths)
6. Input: "Write Findings and Impression"
7. Decision: "Button pressed?" → three branches:
   - SAVE DRAFT → Process "Save findings and impression; status pending → in_progress, otherwise unchanged" → Output "Draft saved" → back to step 5.
   - COMPLETE → Decision "Findings AND Impression filled?" — No → Output "Cannot complete: … must not be empty" → back to step 6. Yes → status = completed → Output "Report completed. Ready to release." → back to step 7.
   - RELEASE → Decision "Findings AND Impression filled?" — No → Output "Cannot release: … must be filled first" → back to step 6. Yes → Process "status = released, released_at = now; stamp radiologist name" → report and images locked.
8. Output: "Result released. Ready to print at the front desk." (no notification is sent)
9. Process: "Receptionist prints the X-ray result (or emails it as PDF)"
10. End.
```

---

## Figure 4.6 — Patient Transfer Flowchart

**What was wrong or missing in the current figure**
- Only **active** requests (in progress or draft) handled by the sender can be transferred, and only to an **active colleague with the same role**.
- A reason is **required** and must be **≤ 1,000 characters**.
- **Cancelling notifies the recipient.** The current figure only shows the sender being notified.
- On Accept, a request is **moved** only if it is still the sender's and still active. Otherwise it is **skipped**.

### Prompt 4.6(a)

```
Draw a flowchart titled "Figure 4.6(a) Sending the Transfer" with standard symbols. Cylinders: diagnostic_requests, users, patient_transfers, patient_transfer_items, notifications. End with off-page connector "B".

1. Start: "Med Tech or Radiologist opens the Transfers panel"
2. Process: "List my transferable patients: active requests (in progress or draft) handled by me and not already in a pending transfer" (diagnostic_requests)
3. Input: "Select one or more patients, a colleague, and a reason"
4. Decision: "At least one patient selected?" — No → Output "Select at least one patient to transfer" → back to step 3.
5. Decision: "Reason entered?" — No → Output "Enter the reason for the transfer" → back.
6. Decision: "Reason ≤ 1,000 characters?" — No → Output "Keep the reason under 1000 characters" → back.
7. Decision: "Recipient is an active colleague with the same role (not me)?" (users) — No → Output "Choose an active colleague with the same role" → back.
8. Decision: "All selected patients still mine and not in another pending transfer?" — No → Output "One of the selected patients is no longer yours to transfer, or is already in a pending transfer" → back.
9. Process: "In one transaction: save transfer status = pending (patient_transfers) and one item per patient (patient_transfer_items)". Decision "Saved?" — No → Rollback → Output "One of these patients was just put in another transfer" → back.
10. Process: "Notify the recipient: transfer request with patient names and reason" (notifications)
11. Output: "Transfer request sent. The patients stay with you until it is accepted."
12. Off-page connector "B"
```

### Prompt 4.6(b)

```
Draw a flowchart titled "Figure 4.6(b) Answering the Transfer" with standard symbols, starting from off-page connector "B". Cylinders: patient_transfers, patient_transfer_items, diagnostic_requests, notifications.

1. Connector "B" — transfer is pending.
2. Decision: "Who acts?"
   - SENDER CANCELS → Decision "Still pending?" — No → Output "<recipient> already <status> this transfer" → End. Yes → Process "status = cancelled" → Process "Notify recipient: transfer withdrawn" → End.
   - RECIPIENT RESPONDS → Decision "Accept or Decline?"
3. DECLINE → Decision "Still pending?" — No → Output "This transfer was already <status>" → End. Yes → Process "status = declined" → Process "Notify sender: transfer declined" → End.
4. ACCEPT → Decision "Still pending?" — No → Output "This transfer was already <status>" → End. Yes → Process "status = accepted".
5. Loop for each transfer item: Decision "Request still active and still handled by the sender?" 
   - Yes → Process "Re-assign request to recipient (assigned_to, and worked-by name if already started)"; item outcome = moved.
   - No → item outcome = skipped.
6. Process "Mark the recipient's transfer notification as read"
7. Process "Notify sender: which patients were moved / skipped"
8. End.
```

---

## Figure 4.7 — Billing and Payment Flowchart

**What was wrong or missing in the current figure**
- Discount buttons are **None, Senior citizen 20%, PWD 20%, Other**. "Other" lets staff set their own **percent (≤ 100%) or fixed peso amount**.
- **Refund marks the payment "refunded".** The current figure says "voided".
- Void **requires** a reason and **no paid amount** on the invoice, then **sets** the balance to 0. Balance 0 is the result, not a precondition.
- A payment amount cannot be more than the remaining balance. Cash tendered is entered separately to compute change.
- **Refund, Void and Sync are Administrator-only.** The Receptionist can create invoices, take payments and print or email receipts.

### Prompt 4.7(a)

```
Draw a flowchart titled "Figure 4.7(a) Flow A — Automatic Invoice after Start" with standard symbols. Cylinders: payments, invoices, invoice_items.

1. Start: "Payment recorded by Start (Figure 4.4)"
2. Decision: "Payment already linked to an invoice?" — Yes → Process "Recalculate that invoice" → End.
3. Decision: "Request already has an invoice?" — Yes → Process "Link payment to existing invoice; recalculate" → End.
4. Process: "Create invoice INV-YYYY-NNNNNN: subtotal = total = payment amount, discount none, status from amount paid" (invoices)
5. Process: "Copy priced services into invoice line items" (invoice_items)
6. Process: "Link payment to invoice; recalculate"
7. Decision: "Any step failed?" — Yes → Process "Log error; payment is kept" → Process "Administrator runs: php spark invoices:backfill (creates invoices for paid payments without one)".
8. End.
```

### Prompt 4.7(b)

```
Draw a flowchart titled "Figure 4.7(b) Flow B — Manual Invoice" with standard symbols. Cylinders: invoices, invoice_items, diagnostic_requests.

1. Start: "Receptionist or Administrator opens New Invoice"
2. Input: "Patient (name, code), optional appointment or diagnostic request"
3. Decision: "Linked request already has an invoice?" — Yes → Output "An invoice already exists for this request: INV-…" → End "Open existing invoice".
4. Decision: "Patient name entered?" — No → Output "Patient name is required" → back to step 2.
5. Input: "Line items: service, category (Lab, X-ray, Other), quantity, unit price"
6. Decision: "At least one valid line item?" — No → Output "At least one line item is required" → back to step 5.
7. Input: "Discount: None, Senior citizen 20%, PWD 20%, or Other (own percent ≤ 100% or fixed ₱, with reason)"
8. Process: "Compute subtotal, discount, total"
9. Process: "Generate invoice number INV-YYYY-NNNNNN; save invoice status = unpaid, balance = total, created by current staff" (invoices)
10. Process: "Save line items with prices frozen at this moment" (invoice_items)
11. Decision: "Invoice linked to a diagnostic request?" — Yes → Decision "Request still pending?" — Yes → Process "Sync invoice tests to the request" (diagnostic_requests). No → skip (sync refused).
12. Output: "Invoice INV-… created"
13. End.
```

### Prompt 4.7(c)

```
Draw a flowchart titled "Figure 4.7(c) Flow C — Taking a Payment" with standard symbols. Cylinders: invoices, payments.

1. Start: "Receptionist or Administrator opens an invoice and clicks Record Payment"
2. Decision: "Invoice status is void or refunded?" — Yes → Output "This invoice cannot receive payments" → End.
3. Input: "Amount, method (cash, card, GCash, PayMaya, insurance), reference no., notes, cash tendered (cash only)"
4. Decision: "Amount > 0?" — No → Output "Payment amount must be greater than zero" → back to step 3.
5. Process: "Recalculate invoice to get the current balance"
6. Decision: "Amount ≤ remaining balance?" — No → Output "Payment is more than the remaining balance" → back to step 3.
7. Decision: "Method = cash and cash tendered entered?" — Yes → Process "Change = tendered − amount".
8. Process: "Save payment, status = paid, received by current staff" (payments). Decision "Saved?" — No → Output "Payment could not be recorded" → End.
9. Process: "Recalculate invoice: amount paid, balance, status → draft (total 0), unpaid, partial, or paid" (invoices)
10. Output: "Payment recorded" → Process "Print receipt or email receipt as PDF"
11. End.
```

### Prompt 4.7(d)

```
Draw a flowchart titled "Figure 4.7(d) Flow D — Corrections by the Administrator" with standard symbols, three branches side by side. Cylinders: payments, invoices, diagnostic_requests.

1. Start: "Administrator opens an invoice"
2. Decision: "Correction?" → REFUND / VOID / SYNC

REFUND branch:
 R1. Input "Select payment; reason (default 'Refunded by admin')"
 R2. Decision "Payment found and not already refunded?" — No → Output "Payment could not be refunded" → End.
 R3. Process "Set payment status = refunded; record void_reason, voided_by, voided_at" (payments)
 R4. Process "Recalculate invoice (refunded amount no longer counts as paid)" → End.

VOID branch:
 V1. Input "Void reason"
 V2. Decision "Reason entered?" — No → Output "A void reason is required" → End.
 V3. Process "Recalculate invoice"
 V4. Decision "Amount paid > 0?" — Yes → Output "Refund the payments first, then void the invoice" → End.
 V5. Process "Set invoice status = void, balance = 0, voided_by, voided_at, void_reason" (invoices) → End.

SYNC branch:
 S1. Decision "Invoice linked to a diagnostic request and has line items?" — No → Output "Could not sync" → End.
 S2. Decision "Request still pending?" — No → Output "The request may already be in progress" → End.
 S3. Process "Rewrite the request's tests to match the invoice" (diagnostic_requests) → Output "Request updated. It now matches the invoice." → End.
```

---

## Figure 4.8 — Context Level Diagram

**What was wrong or missing in the current figure**
- Email is sent **only when a staff member clicks "Email PDF"**. The system never emails patients automatically.
- Booking needs **no account**, and the visitor gets the slot list and a reference number back.
- The Physician role exists in the database but has **no access**, so it is **not** an external entity. Doctor names are typed in as text.

### Prompt 4.8

```
Draw a Context Level Diagram (DFD Level 0 context, Gane-Sarson or Yourdon notation) titled "Figure 4.8 Context Level Diagram of the Polymedic Clinic System".

Center: one process circle "0 — Polymedic Clinic System".
Six external entities (rectangles) placed around it, each with labelled arrows:

1. Public Visitor
   → System: service selection, preferred date, time slot query, patient details (name, age, sex, email, phone, other requests)
   ← System: available time slots, booking errors, booking confirmation with reference number (APPT-…)

2. Receptionist
   → System: login credentials, appointment decisions (approve / cancel / complete), override booking with reason, walk-in registration, Start + staff selection, invoice details, payment details, print/email requests
   ← System: dashboard and monitoring screen, appointment list, notifications, patient codes, invoices, receipts, printed request/results, front-desk and revenue reports

3. Administrator
   → System: login credentials, user accounts, services and prices, clinic hours / closed days / holidays / blocked slots, appointment decisions, walk-in registration, Start + staff selection, refunds, voids, invoice sync
   ← System: dashboard KPIs, notifications, patient and request lists, payment records, billing and revenue reports

4. Medical Technologist
   → System: login credentials, lab results (test, result, unit, reference range, flag), findings, save/complete/release actions, transfer requests and responses
   ← System: assigned lab worklist, new-request and transfer notifications, printable lab results, lab workload report

5. Radiologist
   → System: login credentials, X-ray images, findings and impression, save/complete/release actions, transfer requests and responses
   ← System: assigned X-ray worklist, new-request and transfer notifications, printable X-ray results, radiology workload report

6. Email Recipient (patient or any address entered by staff)
   ← System: emailed PDF (receipt, result, or report) — sent only when staff click "Email PDF"

Keep arrow labels short, place inputs on one side of each entity and outputs on the other, no crossing lines.
```

---

## Figure 4.9 — Level 0 Data Flow Diagram

**What was wrong or missing in the current figure**
- Data stores should be named after the **real tables** (listed below).
- X-ray images are stored as **files**, not in a table.
- **Process 4.0 also creates the invoice.**
- The Email Recipient connects to **8.0 (receipts)** and **9.0 (reports/results)**.

**Data stores (use the same numbers in every DFD):**

| # | Store | # | Store |
|---|---|---|---|
| D1 | users | D8 | invoice_items |
| D2 | patients | D9 | services |
| D3 | appointments | D10 | notifications |
| D4 | diagnostic_requests | D11 | patient_transfers + patient_transfer_items |
| D5 | lab_results | D12 | availability_slots |
| D6 | payments | D13 | clinic_settings |
| D7 | invoices | D14 | X-ray image files |

### Prompt 4.9 (one prompt, three panels)

```
Draw a Level 0 Data Flow Diagram (Gane-Sarson notation: rounded rectangles for processes, open-ended rectangles for data stores, squares for external entities) for the Polymedic Clinic System. Split it into three panels:

Data stores: D1 users, D2 patients, D3 appointments, D4 diagnostic_requests, D5 lab_results, D6 payments, D7 invoices, D8 invoice_items, D9 services, D10 notifications, D11 patient_transfers, D12 availability_slots, D13 clinic_settings, D14 X-ray image files.
External entities: Public Visitor, Receptionist, Administrator, Medical Technologist, Radiologist, Email Recipient.

PANEL (a) "Figure 4.9(a) Front Office — Processes 1.0 to 4.0"
- 1.0 Authenticate User: all staff → credentials; reads D1; writes last login to D1; → staff: dashboard / error.
- 2.0 Book and Manage Appointments: Public Visitor → booking details; reads D9, D12, D13, D3; writes D3, D2; writes D10 (new-appointment notice); Receptionist/Administrator → approve/cancel/complete; Receptionist → override booking; writes D4 (pending requests on approval) and D10 (department notice); → Visitor: slots, reference number.
- 3.0 Register Walk-in Patient: Receptionist/Administrator → walk-in details; reads/writes D2; writes D4; writes D10.
- 4.0 Start and Assign Request: Receptionist/Administrator → Start + staff choice; reads D1 (active staff), D4, D9 (prices); writes D4 (in_progress + assignee), D6 (payment), D7/D8 (invoice).

PANEL (b) "Figure 4.9(b) Departments — Processes 5.0 to 7.0"
- 5.0 Process Laboratory Results: Medical Technologist → results, findings, release; reads D6 (paid?), D4; writes D5, D4; writes D10 (released notice to Admin + Med Techs); → Receptionist: printable lab result.
- 6.0 Process X-ray Reports: Radiologist → images, findings, impression, release; reads D6, D4; writes D14, D4; → Receptionist: printable X-ray result.
- 7.0 Transfer Patients: Med Tech / Radiologist → transfer request, accept/decline/cancel; reads D1, D4; writes D11, D4 (re-assignment), D10.

PANEL (c) "Figure 4.9(c) Billing and Administration — Processes 8.0 and 9.0"
- 8.0 Manage Billing: Receptionist/Administrator → invoice and payment details; Administrator → refund, void, sync; reads/writes D7, D8, D6; reads D2, D9; writes D4 (sync, pending only); → receipts; → Email Recipient: emailed receipt PDF.
- 9.0 Administer System and Reports: Administrator → users, services, clinic hours/holidays/blocked slots; writes D1, D9, D12, D13; reads D3, D4, D6, D7 for reports; all staff → report requests, Email PDF; → staff: reports; → Email Recipient: emailed report/result PDF.

Number every flow label briefly, avoid crossing lines, keep each panel on its own page.
```

---

## Figure 4.10 — Level 1 DFD of Process 2.0 (Book and Manage Appointments)

**What was wrong or missing in the current figure**
- 2.2 reads **clinic_settings** (hours, lunch, interval, closed days, holidays, booking window) and **availability_slots** (blocked slots, capacity).
- 2.4 includes **override booking (Receptionist only)**. It writes to D3 directly as approved.
- 2.3 and 2.5 both write **notifications**.

### Prompt 4.10

```
Draw a Level 1 Data Flow Diagram (Gane-Sarson notation) titled "Figure 4.10 Level 1 DFD of Process 2.0 Book and Manage Appointments".

External entities: Public Visitor, Receptionist, Administrator.
Data stores: D2 patients, D3 appointments, D4 diagnostic_requests, D9 services, D10 notifications, D12 availability_slots, D13 clinic_settings.

Processes and flows:
- 2.1 Validate Booking Form: Public Visitor → booking details (services, date, time, name, age, sex, email, phone, notes); reads D9 (lab and X-ray service list); → 2.2 valid booking; → Visitor: field errors.
- 2.2 Check and Lock Slot: reads D13 (clinic hours, lunch break, slot interval, closed days, holidays, booking window), D12 (blocked slots, capacity), D3 (bookings at that slot); locks the slot rows; → 2.3 confirmed slot; → Visitor: "slot unavailable"; also → Visitor: available time slots (slot lookup).
- 2.3 Save Appointment and Patient: writes D3 (status pending, APPT reference); reads/writes D2 (find or create patient); writes D10 (New Appointment Request → Administrator and Receptionist); → Visitor: confirmation with reference number.
- 2.4 Review Appointment: Receptionist/Administrator → approve, cancel, complete; Administrator → delete; Receptionist → override booking with reason; reads/writes D3; reads D12/D13 (override: open day, not past); → 2.5 approved appointment.
- 2.5 Create Diagnostic Requests: reads D3 services; reads/writes D2 (patient code); writes D4 (one pending request per department, skip if exists); writes D10 (Admin + Med Techs or Radiologists).
```

---

## Figure 4.11 — Level 1 DFD of Process 4.0 (Start and Assign Request)

**What was wrong or missing in the current figure**
- Invoice creation was missing. Add **4.5 Create or Link Invoice**.
- 4.4 reads **D9 services** for prices.

### Prompt 4.11

```
Draw a Level 1 Data Flow Diagram (Gane-Sarson notation) titled "Figure 4.11 Level 1 DFD of Process 4.0 Start and Assign Request".

External entities: Receptionist, Administrator.
Data stores: D1 users, D4 diagnostic_requests, D6 payments, D7 invoices, D8 invoice_items, D9 services.

Processes and flows:
- 4.1 Select Active Staff: Receptionist/Administrator → Start request; reads D1 (active Med Techs for lab or Radiologists for X-ray, with workload); → user: staff list; user → chosen staff → 4.2.
- 4.2 Check Patient Lock: takes per-patient lock; reads D4 (request status pending/cancelled; any other active request for the same patient in either department); → user: "already being handled by …"; → 4.3 cleared request.
- 4.3 Assign Request: re-checks D1 (staff still active); writes D4 (status in_progress, assigned_to_id, assigned_to_name, assigned_at); → 4.4.
- 4.4 Record Payment: reads D6 (payment already exists?); reads D9 (active prices); writes D6 (full amount, cash, paid); → 4.5 payment id; → user: "Payment recorded ₱…".
- 4.5 Create or Link Invoice: reads D6; reads/writes D7 (link to existing invoice for the request or create INV-YYYY-NNNNNN), writes D8 (line items); recalculates D7.
```

---

## Figure 4.12 — Level 1 DFD of Process 8.0 (Manage Billing)

**What was wrong or missing in the current figure**
- "8.5 Recalculate Invoice" appears in both panels. That's acceptable, but give it **one** process box per panel with the same number and add a note that it's the same process.
- 8.3 Sync and 8.6 Refund/Void are **Administrator-only**.
- Billing reports (8.8) are **Administrator** billing reports. The Receptionist's revenue report belongs to 9.0.

### Prompt 4.12(a)

```
Draw a Level 1 Data Flow Diagram (Gane-Sarson notation) titled "Figure 4.12(a) Level 1 DFD of Process 8.0 — Invoicing".

External entities: Receptionist, Administrator, Process 4.0 (Start) shown as an external process reference.
Data stores: D2 patients, D4 diagnostic_requests, D6 payments, D7 invoices, D8 invoice_items, D9 services.

- 8.1 Invoice Start Payment: 4.0 → payment id; reads D6; reads/writes D7 (link to existing invoice for that request, or create one); writes D8; → 8.5. Also triggered by the "invoices:backfill" command for paid payments without an invoice.
- 8.2 Create Manual Invoice: Receptionist/Administrator → patient, optional request, line items, discount (None, Senior 20%, PWD 20%, Other % or ₱); reads D2, D9; reads D7 (one invoice per request — redirect to existing); writes D7 (INV-YYYY-NNNNNN, unpaid) and D8 (frozen prices); → 8.3 if linked to a request.
- 8.3 Sync Tests to Request (Administrator, or automatically after 8.2): reads D7, D8; reads D4 (status must be pending); writes D4 services.
- 8.5 Recalculate Invoice: reads D6 (sum of paid payments), reads/writes D7 (amount paid, balance, status draft/unpaid/partial/paid; void/refunded left unchanged).
```

### Prompt 4.12(b)

```
Draw a Level 1 Data Flow Diagram (Gane-Sarson notation) titled "Figure 4.12(b) Level 1 DFD of Process 8.0 — Payments and Corrections".

External entities: Receptionist, Administrator, Email Recipient.
Data stores: D6 payments, D7 invoices, D8 invoice_items.

- 8.4 Record Payment: Receptionist/Administrator → amount, method (cash, card, GCash, PayMaya, insurance), reference no., cash tendered; reads D7 (status not void/refunded, balance); reads D8; writes D6 (paid, change amount); → 8.5.
- 8.5 Recalculate Invoice (same process as in panel a): reads D6; writes D7.
- 8.6 Refund Payment / Void Invoice (Administrator only): refund → writes D6 (status refunded, reason, by, time) → 8.5; void → reads D7 (reason required, amount paid must be 0) → writes D7 (status void, balance 0, reason, by, time).
- 8.7 Issue Receipt: reads D6, D7, D8; → Receptionist/Administrator: printed receipt; → Email Recipient: receipt PDF (when "Email PDF" is used).
- 8.8 Produce Billing Reports (Administrator): reads D6, D7; → Administrator: billing and revenue reports (refunds shown separately).
```

---

## Figure 4.13 — Entity Relationship Diagram

**What was wrong or missing in the current figure**
- **These tables don't exist: APPOINTMENT_SERVICES, REQUEST_SERVICES, XRAY_IMAGES, TRANSFERS, TRANSFER_ITEMS, CLINIC_AVAILABILITY.**
  - Services are stored as text/JSON columns: `appointments.lab_services` / `xray_services` and `diagnostic_requests.services`.
  - X-ray images are stored in `diagnostic_requests.image_path` / `image_paths` (JSON, up to 5 files).
  - The real transfer tables are `patient_transfers` and `patient_transfer_items`.
  - Clinic availability is split across `availability_slots` (per-slot overrides) and `clinic_settings` (key/value hours, holidays, booking window).
- `diagnostic_requests` holds **both** lab and X-ray requests, told apart by `type`.
- Patients link to requests and invoices by **patient_code**, a logical link with no FK. Appointments become patients by name matching, so there's **no FK** there either.
- **Leave out the legacy/unused tables:** `lab_requests`, `xray_examinations`, `appointment_slots`, `appointment_settings`, and the `*_orphans_20260924` tables.

### Prompt 4.13(a)

```
Draw an Entity Relationship Diagram in crow's-foot notation titled "Figure 4.13(a) Patients, Appointments and Diagnostic Requests". Show each entity with its primary key (PK), foreign keys (FK), and the key attributes listed. Use solid lines for real foreign keys and dashed lines for logical links (matched by value, no FK constraint).

Entities:
- PATIENTS: id PK, patient_code (unique), full_name, email, phone, age, gender, source (online / walk-in / referral), address, created_at
- APPOINTMENTS: id PK, reference_number (unique), appointment_date, appointment_time, full_name, age, gender, email, phone, service_type (laboratory / xray / both), lab_services (JSON), xray_services (JSON), other_requests, status (pending / approved / completed / cancelled), is_override, override_reason, override_by, arrival_time
- DIAGNOSTIC_REQUESTS: id PK, reference_number (unique), type (lab / xray), appointment_id FK, patient_code, patient_name, age, gender, services (text list), request_date, doctor_name, priority, status (pending / in_progress / draft / completed / released / cancelled), assigned_to_id, assigned_to_name, assigned_at, med_tech_id, med_tech_name, radiologist_id, radiologist_name, findings, interpretation, remarks, image_paths (JSON, max 5), released_at
- LAB_RESULTS: id PK, lab_request_id FK, test_name, result, unit, reference_range, flag (normal / high / low / critical)
- SERVICES: id PK, service_code (unique), service_name, category (laboratory / xray / other), charge, is_active
- USERS: id PK, username (unique), full_name, role, status

Relationships:
- APPOINTMENTS 1 —— 0..2 DIAGNOSTIC_REQUESTS (appointment_id FK; at most one per type; walk-in requests have no appointment) — solid.
- DIAGNOSTIC_REQUESTS 1 —— 0..* LAB_RESULTS (lab_request_id FK; lab type only) — solid.
- PATIENTS 1 —— 0..* DIAGNOSTIC_REQUESTS (by patient_code) — dashed.
- APPOINTMENTS 0..* —— 1 PATIENTS ("matched by name, age, sex, email/phone") — dashed.
- USERS 1 —— 0..* DIAGNOSTIC_REQUESTS ("assigned to / worked by": assigned_to_id, med_tech_id, radiologist_id) — dashed.
- SERVICES 1 —— 0..* DIAGNOSTIC_REQUESTS and APPOINTMENTS ("priced / listed by service name") — dashed.
```

### Prompt 4.13(b)

```
Draw an Entity Relationship Diagram in crow's-foot notation titled "Figure 4.13(b) Users, Notifications, Transfers and Clinic Schedule". Show PK, FK and key attributes. Solid lines = foreign keys, dashed = logical links.

Entities:
- USERS: id PK, username (unique), email (unique), password (hashed), full_name, role (admin / receptionist / med_tech / radiologist / physician), prc_license, contact_number, gender, birthdate, address, avatar, status (active / inactive), last_login_at, password_changed_at
- NOTIFICATIONS: id PK, type (appointment / lab / xray / billing / payment / transfer / system), title, message, reference_id, link, user_role, user_id (nullable — set for person-to-person notices like transfers), can_open, is_read, created_at
- PATIENT_TRANSFERS: id PK, type (lab / xray), from_user_id, from_user_name, to_user_id, to_user_name, reason, status (pending / accepted / declined / cancelled), responded_at, created_at
- PATIENT_TRANSFER_ITEMS: id PK, transfer_id, request_id, pending_request_id (unique — a request can be in only one pending transfer), patient_name, reference_number, outcome (moved / skipped)
- DIAGNOSTIC_REQUESTS: id PK, reference_number, type, status, assigned_to_id (show only these attributes)
- AVAILABILITY_SLOTS: id PK, slot_date, slot_time, department (lab / xray), capacity, is_blocked, blocked_reason — unique (slot_date, slot_time, department)
- CLINIC_SETTINGS: id PK, setting_key (unique), setting_value — stores opening/closing time, slot interval, lunch break, closed weekdays, holidays, booking horizon

Relationships:
- USERS 1 —— 0..* PATIENT_TRANSFERS as sender (from_user_id) — dashed.
- USERS 1 —— 0..* PATIENT_TRANSFERS as recipient (to_user_id) — dashed.
- PATIENT_TRANSFERS 1 —— 1..* PATIENT_TRANSFER_ITEMS (transfer_id) — dashed.
- PATIENT_TRANSFER_ITEMS 0..* —— 1 DIAGNOSTIC_REQUESTS (request_id) — dashed.
- USERS 1 —— 0..* NOTIFICATIONS (user_id; role-wide notices use user_role instead) — dashed.
- AVAILABILITY_SLOTS and CLINIC_SETTINGS stand alone (no relationships); add a note: "read by the booking process to decide which slots are open".
```

### Prompt 4.13(c)

```
Draw an Entity Relationship Diagram in crow's-foot notation titled "Figure 4.13(c) Billing". Show PK, FK and key attributes. Solid lines = foreign keys, dashed = logical links.

Entities:
- INVOICES: id PK, invoice_no (unique, INV-YYYY-NNNNNN), patient_code, patient_name, appointment_id, request_id, request_type (lab / xray), subtotal, discount_type (none / percent / fixed), discount_value, discount_reason, tax, total, amount_paid, balance, status (draft / unpaid / partial / paid / void / refunded), created_by, created_by_id, voided_by, voided_at, void_reason
- INVOICE_ITEMS: id PK, invoice_id, service_name, category, qty, unit_price, line_total
- PAYMENTS: id PK, reference_number (unique), invoice_id, request_id FK, request_type, appointment_id FK, patient_code, patient_name, services (JSON), total_amount, amount_paid, change_amount, payment_method (cash / card / gcash / paymaya / insurance), reference_no, payment_status (paid / pending / refunded / failed), payment_date, received_by, received_by_id, void_reason, voided_at, voided_by
- APPOINTMENTS: id PK, reference_number (show only these)
- DIAGNOSTIC_REQUESTS: id PK, reference_number, type (show only these)
- PATIENTS: id PK, patient_code (show only these)
- USERS: id PK, full_name (show only these)

Relationships:
- INVOICES 1 —— 1..* INVOICE_ITEMS (invoice_id) — dashed.
- INVOICES 1 —— 0..* PAYMENTS (invoice_id) — dashed.
- DIAGNOSTIC_REQUESTS 1 —— 0..* PAYMENTS (request_id FK) — solid.
- APPOINTMENTS 1 —— 0..* PAYMENTS (appointment_id FK) — solid.
- DIAGNOSTIC_REQUESTS 1 —— 0..1 INVOICES (request_id + request_type; one invoice per request) — dashed.
- APPOINTMENTS 1 —— 0..* INVOICES (appointment_id) — dashed.
- PATIENTS 1 —— 0..* INVOICES and PAYMENTS (patient_code) — dashed.
- USERS 1 —— 0..* PAYMENTS ("received by", received_by_id) and INVOICES ("created by", created_by_id) — dashed.
```

---

## Figure 4.14 — Use Case Diagram

**What was wrong or missing in the current figure**
- **Override booking is Receptionist-only.** **Delete appointment is Administrator-only.**
- Administrators **can't** enter the Med Tech or Radiologist pages. They can register walk-ins and press Start.
- Med Tech and Radiologist each have a **department workload report**, which the current figure is missing.
- Billing: the Receptionist has **create invoice, record payment, print/email receipt, search invoices**. **Refund, void, sync and billing reports are Administrator-only.**
- Printing a result is available to the Receptionist, Administrator and the department that released it.

### Prompt 4.14(a)

```
Draw a UML Use Case Diagram titled "Figure 4.14(a) Booking, Front-Desk and Diagnostic Modules". System boundary box labelled "Polymedic Clinic System". Actors (stick figures): Public Visitor (left), Receptionist (left), Administrator (left), Medical Technologist (right), Radiologist (right). Use «include» and «extend» arrows where noted.

Use cases and associations:
1. Check Available Slots — Public Visitor
2. Book Appointment Online — Public Visitor («include» Check Available Slots)
3. View Booking Confirmation — Public Visitor
4. Approve / Cancel / Complete Appointment — Receptionist, Administrator
5. Delete Appointment — Administrator
6. Override-Book a Full Slot — Receptionist (standalone use case; requires a reason)
7. Register Walk-in Patient — Receptionist, Administrator
8. Start Request and Assign Staff — Receptionist, Administrator («include» Record Start Payment)
9. Record Start Payment — (included only)
10. Print Request / Result — Receptionist, Administrator, Medical Technologist, Radiologist
11. Enter and Release Lab Results — Medical Technologist (Save draft / Complete / Release)
12. Upload X-ray Images — Radiologist
13. Write and Release X-ray Report — Radiologist (Upload X-ray Images «extend» this use case)
14. Transfer Patients to a Colleague — Medical Technologist, Radiologist
15. Accept / Decline / Cancel Transfer — Medical Technologist, Radiologist («extend» Transfer Patients to a Colleague)
16. View Department Workload Report — Medical Technologist, Radiologist
```

### Prompt 4.14(b)

```
Draw a UML Use Case Diagram titled "Figure 4.14(b) Billing Module". System boundary "Polymedic Clinic System — Billing". Actors: Receptionist (left), Administrator (right).

Use cases and associations:
1. Search / View Invoices — Receptionist, Administrator
2. Create Invoice (with discount: None, Senior 20%, PWD 20%, Other) — Receptionist, Administrator
3. Record Payment — Receptionist, Administrator
4. Print or Email Receipt — Receptionist, Administrator («extend» Record Payment)
5. Refund Payment — Administrator
6. Void Invoice — Administrator
7. Sync Invoice to Request — Administrator
8. View Billing and Revenue Reports — Administrator
```

### Prompt 4.14(c)

```
Draw a UML Use Case Diagram in black and white, titled "Figure 4.14(c) Use Case Diagram — Administration and Common Modules".

Layout: one solid system boundary box labelled "Polymedic Clinic System". Inside it, three dashed sub-boxes stacked top to bottom, each with an italic label in its top-left corner: "Administration Module", "Front Desk Module", "Common Module". Use cases are ovals. Associations are plain solid lines (no arrowheads).

Actors (stick figures with bold names):
- Right side: Administrator
- Left side: Receptionist
- Left side, below Receptionist: All staff
- Bottom (outside the boundary): Medical Technologist and Radiologist
Draw a generalization arrow (solid line with hollow triangle pointing to "All staff") from EACH of: Administrator, Receptionist, Medical Technologist, Radiologist. These four actors use the Common Module only through "All staff", so do NOT also draw direct lines from them to Common Module use cases.

Administration Module (associated with Administrator only):
1. Manage user accounts
2. Manage services and prices
3. Configure clinic availability

Front Desk Module (associated with Receptionist only):
4. View front-desk and revenue reports

Common Module (associated with All staff only):
5. View dashboard
6. View and manage notifications
7. Log in / Log out
8. Manage My Profile
9. Email document as PDF

Keep lines from crossing where possible: Administrator lines go left into the Administration Module; Receptionist line goes right into the Front Desk Module; All staff lines go right into the Common Module.
```
