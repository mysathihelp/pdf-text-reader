✅ Blueprint: WordPress Plugin — “pdf-text-reader”
🎯 Plugin Purpose
WordPress admin से कोई PDF (जैसे biodata) upload किया जाएगा।


Plugin PDF से text extract करेगा।


Extract किया हुआ text उसी पेज पर नीचे display करेगा।


सिर्फ testing/demo purpose के लिए simple और clean functionality।



🧩 Plugin Structure
pdf-text-reader/
│
├── pdf-text-reader.php         → मुख्य plugin file
├── admin/
│   └── admin-page.php          → Upload form + text display UI
├── includes/
│   └── pdf-reader.php          → PDF text extraction logic
└── assets/
    └── style.css               → Simple admin styling


🔧 Features List
1. Admin Menu
WordPress dashboard में नया menu:

 PDF Text Reader
└── Upload Biodata


2. Upload Form (Admin Page)
File input for PDF


“Extract Text” बटन


3. Text Extraction
PHP library used:


Either smalot/pdfparser (Composer-based)


OR WordPress-compatible raw parser (embedded)
 ✔️ simplicity के लिए हम internal PDF parser इस्तेमाल करेंगे ताकि composer dependency न पड़े।


4. Output Display
Admin पैनल में upload के नीचे ही PDF का extracted text दिखाई देगा।



📤 GitHub Plan (Repository Structure)
You will create a repo on GitHub like:
repo-name: pdf-text-reader

Inside:
/pdf-text-reader
    pdf-text-reader.php
    /admin
       admin-page.php
    /includes
       pdf-reader.php
    /assets
       style.css
README.md
LICENSE

README will include:
How to install plugin in WordPress


Screenshot demo


Contribute notes



🧪 Testing Plan (On Your Existing Website)
1. GitHub → ZIP download
Download plugin ZIP from GitHub release


2. Install on WordPress testing site
Go to Plugins > Add New > Upload Plugin


Choose ZIP


Install


Activate


3. Test functionality
Go to PDF Text Reader → Upload Biodata


Choose any biodata PDF


Extract → Check output



🔄 Workflow Using ChatGPT (Your Goal According to Message)
Here’s how you will use ChatGPT at each stage:
Step 1 — Blueprint (Completed)
✔️ Already done
Step 2 — Plugin Code Generation
➡️ You will say:
 “plugin ka full code generate karo”
 Then I will write all files for you.
Step 3 — GitHub Upload Guide
I will give you:
Files in proper folder structure


GitHub instructions


Commands


README template


Step 4 — Implementation / Debugging
You will test it on your site
 → If anything breaks, say:
 “error fix karo”
 And I’ll fix it.
