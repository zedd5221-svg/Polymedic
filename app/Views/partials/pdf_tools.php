<?php
/* ------------------------------------------------------------------
   PDF soft copy: "Download PDF" and "Email PDF" for a printable page.

   Include it inside the page's screen-only toolbar; the buttons pick up
   the toolbar's own button styles. The PDF is made in the browser from
   the page's paper (html2canvas + jsPDF), so what is saved or sent is
   what prints. Email posts that PDF to ReportMail::send, which mails it.

   $pdf = [
     'target'   => CSS selector of the paper element(s), e.g. '.sheet'.
                   Each match starts on a new page (a result, then one
                   page per image plate); a long one flows onto more pages.
     'filename' => file name without .pdf, e.g. 'LAB-2026-0012 Juan Cruz'
     'label'    => what the document is, e.g. 'laboratory result'
     'emailTo'  => default recipient (the patient's email) or ''
     'subject'  => default email subject
     'message'  => default email message
   ]

   Pages can style the PDF copy with `html.pdfx-rendering ...` rules.
   ------------------------------------------------------------------ */

$pdf      = is_array($pdf ?? null) ? $pdf : [];
$clinic   = config('Clinic');
$label    = (string) ($pdf['label'] ?? 'report');
$filename = trim((string) preg_replace('/[^\w\- .()]+/u', '', (string) ($pdf['filename'] ?? 'report'))) ?: 'report';
$subject  = (string) ($pdf['subject'] ?? ucfirst($label) . ' · ' . $clinic->name);
$message  = (string) ($pdf['message'] ?? "Good day,\n\nPlease find attached your " . $label . " from " . $clinic->name . ".\n\nThank you.");

$config = [
    'target'   => (string) ($pdf['target'] ?? '.sheet'),
    'filename' => $filename . '.pdf',
    'endpoint' => base_url('report/email-pdf'),
    'csrfName' => csrf_token(),
    'csrfHash' => csrf_hash(),
];
?>
<button type="button" data-pdfx="download"><span aria-hidden="true">⤓</span> Download PDF</button>
<button type="button" data-pdfx="email"><span aria-hidden="true">✉</span> Email PDF</button>

<dialog class="pdfx-dialog" id="pdfxDialog" aria-labelledby="pdfxTitle">
    <form method="dialog" class="pdfx-form" id="pdfxForm" novalidate>
        <h2 id="pdfxTitle">Email PDF</h2>
        <p class="pdfx-sub">Sends <strong><?= esc($filename) ?>.pdf</strong> as an attachment.</p>

        <label for="pdfxTo">To</label>
        <input type="email" id="pdfxTo" required autocomplete="email" placeholder="patient@example.com"
               value="<?= esc((string) ($pdf['emailTo'] ?? ''), 'attr') ?>">

        <label for="pdfxSubject">Subject</label>
        <input type="text" id="pdfxSubject" maxlength="150" required value="<?= esc($subject, 'attr') ?>">

        <label for="pdfxMessage">Message</label>
        <textarea id="pdfxMessage" rows="5" maxlength="2000"><?= esc($message) ?></textarea>

        <p class="pdfx-status" id="pdfxStatus" role="status" aria-live="polite" hidden></p>

        <div class="pdfx-actions">
            <button type="button" class="pdfx-btn" id="pdfxCancel">Cancel</button>
            <button type="submit" class="pdfx-btn pdfx-btn--primary" id="pdfxSend">Send</button>
        </div>
    </form>
</dialog>

<style>
    .pdfx-dialog {
        width: min(460px, calc(100vw - 32px)); padding: 0; border: 0; border-radius: 12px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25); color: #0f172a;
        font: 14px/1.45 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    }
    .pdfx-dialog::backdrop { background: rgba(15, 23, 42, 0.45); }
    .pdfx-form { display: flex; flex-direction: column; padding: 20px; }
    .pdfx-form h2 { margin: 0; font-size: 17px; }
    .pdfx-sub { margin: 4px 0 14px; font-size: 13px; color: #64748b; word-break: break-word; }
    .pdfx-form label { margin: 10px 0 4px; font-size: 12px; font-weight: 600; color: #334155; }
    .pdfx-form input, .pdfx-form textarea {
        width: 100%; box-sizing: border-box; padding: 8px 10px; font: inherit; color: inherit;
        border: 1px solid #cbd5e1; border-radius: 7px; background: #fff;
    }
    .pdfx-form textarea { resize: vertical; }
    .pdfx-form input:focus, .pdfx-form textarea:focus { outline: none; border-color: #1976d2; box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.2); }
    .pdfx-status { margin: 12px 0 0; padding: 8px 10px; font-size: 13px; border-radius: 7px; background: #eff6ff; color: #1e3a8a; }
    .pdfx-status.is-error { background: #fef2f2; color: #991b1b; }
    .pdfx-status.is-ok { background: #f0fdf4; color: #166534; }
    .pdfx-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
    .pdfx-btn {
        height: 36px; padding: 0 16px; font: 600 13px system-ui, sans-serif; color: #334155;
        background: #fff; border: 1px solid #cbd5e1; border-radius: 7px; cursor: pointer;
    }
    .pdfx-btn--primary { color: #fff; background: #1976d2; border-color: #1976d2; }
    .pdfx-btn:disabled, [data-pdfx]:disabled { opacity: 0.6; cursor: wait; }

    /* The off-screen copy the PDF is drawn from: A4 wide, paper only. */
    .pdfx-host { position: absolute; left: -10000px; top: 0; }
    .pdfx-page { width: 794px; background: #fff; }
    .pdfx-sheet { box-shadow: none !important; margin-top: 0 !important; margin-bottom: 0 !important; }

    @media print { [data-pdfx], .pdfx-dialog, .pdfx-host { display: none !important; } }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" defer></script>
<script>
(function () {
    'use strict';

    var CFG = <?= json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;
    var MAX_BYTES = 10 * 1024 * 1024;

    var A4_W_MM  = 210;
    var A4_H_MM  = 297;
    var PAGE_PX  = 794;   // A4 width at 96 dpi; the copy is laid out this wide
    var SCALE    = 2;     // render resolution
    var MARGIN   = 10;    // mm above / below where a sheet is continued
    var WHITE    = 248;   // a pixel this light counts as background

    var dialog = document.getElementById('pdfxDialog');
    var form   = document.getElementById('pdfxForm');
    var status = document.getElementById('pdfxStatus');
    var send   = document.getElementById('pdfxSend');

    function say(text, tone) {
        status.textContent = text;
        status.className = 'pdfx-status' + (tone ? ' is-' + tone : '');
        status.hidden = !text;
    }

    /* ---------- Making the PDF ---------- */

    /* Inline SVG (report charts) is styled by page CSS, which html2canvas
       neither applies inside the SVG nor uses to size it. Copy the
       original's computed drawing styles onto the copy, then swap the
       copy for an <img> of itself sized by CSS, which html2canvas draws
       faithfully. */
    var SVG_STYLES = ['fill', 'fill-opacity', 'stroke', 'stroke-width', 'stroke-dasharray', 'stroke-linecap',
                      'stroke-opacity', 'opacity', 'font-family', 'font-size', 'font-weight', 'letter-spacing'];

    function fixSvgs(original, copy) {
        var from = original.querySelectorAll('svg');
        var to   = copy.querySelectorAll('svg');
        for (var s = 0; s < from.length && s < to.length; s++) {
            var box = from[s].getBoundingClientRect();
            if (!box.width) { continue; }
            var vb = from[s].viewBox && from[s].viewBox.baseVal;
            var w  = vb && vb.width ? vb.width : box.width;
            var h  = vb && vb.height ? vb.height : box.height;

            var a = from[s].querySelectorAll('*');
            var b = to[s].querySelectorAll('*');
            for (var i = 0; i < a.length && i < b.length; i++) {
                var cs = getComputedStyle(a[i]);
                SVG_STYLES.forEach(function (p) { b[i].style.setProperty(p, cs.getPropertyValue(p)); });
            }

            to[s].setAttribute('xmlns', 'http://www.w3.org/2000/svg');
            to[s].setAttribute('width', w);
            to[s].setAttribute('height', h);
            var img = new Image();
            img.alt = '';
            img.style.cssText = 'display:block;width:100%;height:auto;';
            img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(new XMLSerializer().serializeToString(to[s]));
            to[s].parentNode.replaceChild(img, to[s]);
        }
    }

    function imagesReady(el) {
        return Promise.all(Array.prototype.map.call(el.querySelectorAll('img'), function (img) {
            return img.complete ? Promise.resolve() : new Promise(function (r) { img.onload = img.onerror = r; });
        }));
    }

    function isWhiteRow(px, row, width) {
        for (var i = row * width * 4, end = i + width * 4; i < end; i += 4) {
            if (px[i] < WHITE || px[i + 1] < WHITE || px[i + 2] < WHITE) { return false; }
        }
        return true;
    }

    /* Cut a tall sheet into A4 pages. Each cut moves up to the nearest
       all-white row in the bottom quarter of the page — a gap between
       lines or blocks — so text, rows and charts are not sliced. A
       trailing slice that is only white (bottom padding) is dropped. */
    function slices(canvas) {
        var w      = canvas.width;
        var pageH  = Math.floor(w * A4_H_MM / A4_W_MM);
        var margin = Math.round(w * MARGIN / A4_W_MM);
        var ctx    = canvas.getContext('2d');
        var out    = [];

        for (var y = 0; y < canvas.height;) {
            var top = out.length ? margin : 0;          // continued pages get a top margin
            var room = pageH - top;
            var h;
            if (canvas.height - y <= room) {
                h = canvas.height - y;                  // the rest fits
            } else {
                room -= margin;                         // leave a bottom margin too
                var lo = Math.floor(room * 0.75);
                var hi = room;
                // Only a sliver would spill over (e.g. a table's total row):
                // cut earlier so the next page carries a real block.
                var spill = canvas.height - y - room;
                if (spill < pageH * 0.15) {
                    lo = Math.floor(room * 0.5);
                    hi = Math.max(lo + 1, canvas.height - y - Math.floor(pageH * 0.15));
                }
                var band = ctx.getImageData(0, y + lo, w, hi - lo).data;
                h = room;
                for (var r = hi - lo - 1; r >= 0; r--) {
                    if (isWhiteRow(band, r, w)) { h = lo + r + 1; break; }
                }
            }

            var last = y + h >= canvas.height;
            var blank = false;
            if (last && out.length) {
                var px = ctx.getImageData(0, y, w, h).data;
                blank = true;
                for (var row = 0; row < h && blank; row++) { blank = isWhiteRow(px, row, w); }
            }
            if (!blank) {
                var piece = document.createElement('canvas');
                piece.width = w;
                piece.height = h;
                piece.getContext('2d').drawImage(canvas, 0, y, w, h, 0, 0, w, h);
                out.push({ data: piece.toDataURL('image/jpeg', 0.92), top: top, height: h });
            }
            y += h;
        }
        return out;
    }

    /* The whole document as a jsPDF: every sheet from a fresh page. */
    function makePdf() {
        if (typeof window.html2canvas !== 'function' || !window.jspdf) {
            return Promise.reject(new Error('The PDF tool could not load. Check the internet connection and try again.'));
        }
        var sheets = Array.prototype.slice.call(document.querySelectorAll(CFG.target));
        if (!sheets.length) { return Promise.reject(new Error('Nothing to save on this page.')); }

        var pdf   = new window.jspdf.jsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait', compress: true });
        var pages = 0;
        var host  = document.createElement('div');
        host.className = 'pdfx-host';
        document.body.appendChild(host);
        document.documentElement.classList.add('pdfx-rendering');

        var chain = Promise.resolve();
        sheets.forEach(function (sheet) {
            chain = chain.then(function () {
                var page = document.createElement('div');
                page.className = 'pdfx-page';
                var copy = sheet.cloneNode(true);
                copy.classList.add('pdfx-sheet');
                page.appendChild(copy);
                host.appendChild(page);
                fixSvgs(sheet, copy);

                return imagesReady(copy).then(function () {
                    return window.html2canvas(page, {
                        scale: SCALE, useCORS: true, backgroundColor: '#ffffff', logging: false,
                        // laid out as on a desktop, whatever the window size
                        windowWidth: PAGE_PX
                    });
                }).then(function (canvas) {
                    host.removeChild(page);
                    var mmPerPx = A4_W_MM / canvas.width;
                    slices(canvas).forEach(function (s) {
                        if (pages++) { pdf.addPage(); }
                        pdf.addImage(s.data, 'JPEG', 0, s.top * mmPerPx, A4_W_MM, s.height * mmPerPx);
                    });
                });
            });
        });

        function cleanUp() {
            host.remove();
            document.documentElement.classList.remove('pdfx-rendering');
        }
        return chain.then(function () { cleanUp(); return pdf; }, function (err) { cleanUp(); throw err; });
    }

    /* ---------- Buttons ---------- */

    function busy(btn, on, text) {
        if (on) { btn.dataset.label = btn.innerHTML; btn.textContent = text; }
        else if (btn.dataset.label) { btn.innerHTML = btn.dataset.label; }
        btn.disabled = on;
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-pdfx]');
        if (!btn) { return; }

        if (btn.dataset.pdfx === 'email') {
            say('');
            send.disabled = false;
            dialog.showModal();
            return;
        }

        busy(btn, true, 'Preparing PDF…');
        makePdf()
            .then(function (pdf) { pdf.save(CFG.filename); })
            .catch(function (err) { alert(err.message || 'The PDF could not be created. Please try again.'); })
            .then(function () { busy(btn, false); });
    });

    document.getElementById('pdfxCancel').addEventListener('click', function () { dialog.close(); });

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var to = document.getElementById('pdfxTo');
        var subject = document.getElementById('pdfxSubject');
        if (!to.value.trim() || !to.checkValidity()) { say('Enter a valid email address.', 'error'); to.focus(); return; }
        if (!subject.value.trim()) { say('Enter a subject.', 'error'); subject.focus(); return; }

        send.disabled = true;
        say('Creating the PDF…');

        makePdf()
            .then(function (pdf) {
                var blob = pdf.output('blob');
                if (blob.size > MAX_BYTES) { throw new Error('This PDF is larger than 10 MB, too big to email. Download it instead.'); }

                say('Sending to ' + to.value.trim() + '…');
                var body = new FormData();
                body.append(CFG.csrfName, CFG.csrfHash);
                body.append('to', to.value.trim());
                body.append('subject', subject.value.trim());
                body.append('message', document.getElementById('pdfxMessage').value);
                body.append('pdf', blob, CFG.filename);

                return fetch(CFG.endpoint, {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function (r) {
                    return r.json().catch(function () { throw new Error('The server did not answer (HTTP ' + r.status + ').'); });
                });
            })
            .then(function (data) {
                if (!data || !data.success) { throw new Error((data && data.message) || 'The email could not be sent.'); }
                say(data.message, 'ok');
                setTimeout(function () { if (dialog.open) { dialog.close(); } }, 1800);
            })
            .catch(function (err) {
                say(err.message || 'The email could not be sent.', 'error');
                send.disabled = false;
            });
    });
})();
</script>
