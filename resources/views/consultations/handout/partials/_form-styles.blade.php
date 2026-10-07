<style>
    @page {
        size: 8.5in 13in;
        margin: 0.5in;
    }

    * {
        box-sizing: border-box;
    }

    html, body {
        margin: 0;
        padding: 0;
        background: #fff;
        color: #000;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 8pt;
        line-height: 1.12;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    p {
        margin: 0;
    }

    .iclinic-sheet {
        width: 100%;
        margin: 0 auto;
        page-break-after: always;
        page-break-inside: avoid;
        background: #fff;
    }

    .iclinic-sheet:last-child {
        page-break-after: auto;
    }

    {{-- Fixed-page canvas: exactly the 7.5in x 12in printable area (@page minus 0.5in margins).
         Every block inside is pinned to this canvas so fields keep exact coordinates on print. --}}
    .iclinic-form {
        position: relative;
        width: 720px;
        height: 1152px;
        margin: 0 auto;
        overflow: hidden;
        border: 2px solid #000;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 8pt;
        line-height: 1.12;
        color: #000;
        background: #fff;
    }

    .form-body {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 22px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .form-head,
    .form-section {
        flex: 0 0 auto;
        min-height: 0;
    }

    .form-section:last-child {
        flex: 1 1 auto;
    }

    .form-section {
        overflow: hidden;
    }

    .form-section > table.form-table {
        height: 100%;
    }

    table.form-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    table.form-table td,
    table.form-table th {
        border: 1px solid #000;
        padding: 1px 3px;
        vertical-align: top;
        word-wrap: break-word;
        overflow-wrap: break-word;
        overflow: hidden;
    }

    .section-header {
        background: #0d4a3c;
        color: #fff;
        font-weight: bold;
        font-size: 8pt;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        padding: 2px 4px !important;
        vertical-align: middle !important;
    }

    .label-cell {
        background: #d1d5db;
        font-weight: bold;
        font-size: 8pt;
        vertical-align: middle !important;
    }

    .label-cell-sm {
        background: #d1d5db;
        font-weight: bold;
        font-size: 7pt;
        vertical-align: middle !important;
    }

    .sub-label {
        font-weight: normal;
        font-size: 7pt;
    }

    .field-label {
        font-weight: bold;
        font-size: 8pt;
        margin-bottom: 0;
        line-height: 1.1;
    }

    .field-help {
        font-weight: normal;
        font-size: 7pt;
        line-height: 1.1;
    }

    .field-value {
        min-height: 12px;
        font-size: 9pt;
        line-height: 1.15;
    }

    .field-value-sm {
        min-height: 11px;
        font-size: 8pt;
        line-height: 1.15;
    }

    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-bold { font-weight: bold; }
    .text-upper { text-transform: uppercase; }
    .whitespace-pre { white-space: pre-wrap; }

    {{-- Line clamps for variable-length values: keeps fixed row heights stable on print. --}}
    .clip,
    .clip-1,
    .clip-2,
    .clip-3,
    .clip-4 {
        overflow: hidden;
    }

    .clip-1 { max-height: calc(1 * 1.15em); }
    .clip-2 { max-height: calc(2 * 1.15em); }
    .clip-3 { max-height: calc(3 * 1.15em); }
    .clip-4 { max-height: calc(4 * 1.15em); }

 

    .bhcis-brand-row {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .bhcis-logo {
        width: 34px;
        height: 34px;
        display: block;
        flex-shrink: 0;
    }

    .bhcis-brand-name {
        font-size: 9.5pt;
        font-weight: bold;
        line-height: 1.1;
    }

    .bhcis-brand-facility {
        font-size: 7.5pt;
        line-height: 1.1;
    }

    .bhcis-brand-note {
        font-size: 6.5pt;
        font-style: italic;
        color: #4b5563;
        line-height: 1.1;
        margin-top: 1px;
    }

    .bhcis-title-cell {
        text-align: center;
        vertical-align: middle !important;
        padding: 4px 6px !important;
    }

    .bhcis-form-title {
        font-size: 11.5pt;
        font-weight: bold;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        line-height: 1.15;
    }

    .bhcis-meta .meta-cell {
        padding: 2px 5px;
    }

    .bhcis-meta .meta-label {
        display: block;
        font-size: 6.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #4b5563;
        line-height: 1.1;
    }

    .bhcis-meta .meta-value {
        display: block;
        font-size: 9pt;
        font-weight: bold;
        line-height: 1.15;
        min-height: 11px;
    }

    .instructions {
        border: 1px solid #000;
        padding: 2px 4px;
        font-size: 7pt;
        font-style: italic;
        line-height: 1.15;
    }

    .form-footer {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 22px;
        border: 1px solid #000;
        border-top: 0;
        background: #0d4a3c;
        color: #fff;
        font-size: 7pt;
        font-weight: bold;
        padding: 0 4px;
        display: flex;
        align-items: center;
    }

    .form-footer-flex {
        justify-content: space-between;
    }

    .mark {
        display: inline-flex;
        align-items: flex-start;
        gap: 2px;
        margin-right: 4px;
        vertical-align: top;
    }

    .mark-block {
        display: flex;
        align-items: flex-start;
        gap: 2px;
        margin-bottom: 0;
        line-height: 1.1;
    }

    .mark-box {
        display: inline-block;
        width: 10px;
        height: 10px;
        min-width: 10px;
        border: 1px solid #000;
        text-align: center;
        font-size: 7pt;
        font-weight: bold;
        line-height: 10px;
        flex-shrink: 0;
    }

    .mark-label {
        font-size: 8pt;
        line-height: 1.1;
    }

    .marks-2col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 4px;
    }

    .marks-stack {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .am-pm-box {
        display: inline-flex;
        border: 1px solid #000;
        font-size: 7pt;
        margin-left: 2px;
        vertical-align: middle;
    }

    .am-pm-box span { padding: 0 3px; }
    .am-pm-box span.active {
        background: #d1d5db;
        font-weight: bold;
    }
    .am-pm-box .sep {
        border-left: 1px solid #000;
        border-right: 1px solid #000;
        padding: 0 2px;
    }

    .consent-col-title {
        background: #d1d5db;
        font-weight: bold;
        text-align: center;
        font-size: 8pt;
        padding: 2px;
        border-bottom: 1px solid #000;
    }

    .consent-text {
        padding: 3px;
        font-size: 7pt;
        text-align: justify;
        line-height: 1.15;
    }

    .consent-text p { margin-bottom: 1px; }

    .sig-line {
        margin-top: 16px;
        border-top: 1px dotted #000;
    }

    table.form-table.nested-table > tbody > tr > td,
    table.form-table.nested-table > tbody > tr > th {
        border-left: 0;
        border-right: 0;
    }

    table.form-table.nested-table > tbody > tr:first-child > td,
    table.form-table.nested-table > tbody > tr:first-child > th {
        border-top: 0;
    }

    table.form-table.nested-table > tbody > tr:last-child > td,
    table.form-table.nested-table > tbody > tr:last-child > th {
        border-bottom: 0;
    }

    .no-print { display: block; }

    @media print {
        .no-print { display: none !important; }
        body { background: #fff !important; }
        .iclinic-sheet { box-shadow: none !important; }
        main { padding: 0 !important; }
    }

    @media screen {
        body.preview-body {
            background: #e8e4dc;
            padding: 12px 0 24px;
        }

        .iclinic-sheet {
            max-width: 198mm;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            margin-bottom: 16px;
            overflow-x: auto;
        }
    }
</style>
