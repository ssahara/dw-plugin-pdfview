# DokuWiki PdfView plugin (仮)

DokuWiki にmedia フォルダにアップロードした PDF ファイルをページ上で閲覧できるようにします。

## PDF.js Viewer

[PDF.js](https://mozilla.github.io/pdf.js/) は、Mozilla Foundation が Firefox の PDFビューアとして開発しており、JavaScript で作られています。
PDFの読み込みを行うパーサ、描画を行うレンダラ、画面UIのビューアのセットです。

JavaScriptでPDFを読み込んでHTML5のCanvasに描画する形式であるため、
そのHTMLファイルを`<iframe>`で埋め込み表示することにより、
PDFを直接表示できないブラウザでも 表示させることが可能です。

        {{pdfjs [size] > :ns:some.pdf | title}}
        {{pdfjs [size] > :ns:some.pdf#page=5 | title}}
        {{pdfjs [size] > :ns:some.pdf#page=10&zoom=page-fit | title}}

## Slide Viewer

PDFファイルをスライド風に表示します。 
[azu/slide-pdf.js](https://github.com/azu/slide-pdf.js) を DokuWiki のシンタックス プラグインに仕立てたものです。

        {{slide [size] > :ns:some.pdf | title}}
