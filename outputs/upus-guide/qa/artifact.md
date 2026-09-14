# UPUS SKB12 V3.1 manual production contract

Reference: C:/Users/ttpho/.codex/plugins/cache/openai-curated-remote/openai-templates/0.1.1/skills/artifact-template-investment-committee-memo/assets/reference.docx
Reference render: reference.pdf and reference-contact.png. 12 pages, one Letter portrait section, one-inch margins on all sides. Preserve section geometry, theme, numbering and underlying relationships in the cloned document. Retain black title and heading hierarchy, grayscale tables and restrained header/footer pattern. The source cover, narrative sections, comparative tables and figures are the four reusable patterns.

Content mapping: replace the cover title and metadata with the Raspberry Pi manual title and reading instructions; replace financial narrative, tables and example figures with hardware explanations, connector comparisons, original board photographs and explanatory schematics. Remove investment/confidentiality wording because it is unsupported by the user's subject. Expand the narrative and figure patterns to accommodate all board topics. Preserve 26/20/16 point Title/Heading1/Heading2 roles. Use Tahoma for Thai legibility and set body at 11 points with 1.12 line spacing. This is a language adaptation of inherited source styles. Preserve black/gray visual system, Letter page size and margins. Tables are repeated records/comparisons rather than paragraph containers.

Slots: word/document.xml body is fully editable subject matter; word/header1.xml and word/footer1.xml text fields editable for title and page number; pictures added as image relationships. All finance-only media become unreferenced after body replacement. Preserve section properties. New images are diagrams or original user images embedded without retouching. Source is never overwritten. No copied finance text remains.

Render method: installed Word COM in a hidden application; PDF pages rasterized by the packaged render_docx.py through a local converter adapter using the Word PDF, because bundled LibreOffice is unavailable on Windows. Refresh fields in Word. QA all output pages, check text/glyphs/overflow and source hash. Artifact inventory and hashes are produced by the builder.

Reuse the already inspected Thai guide visual system. All subject content is replaced with the UPUS guide. Original photograph is embedded unchanged.
