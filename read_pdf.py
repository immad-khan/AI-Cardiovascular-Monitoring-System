import sys

try:
    from pypdf import PdfReader
    reader = PdfReader("BCS223148_Ehsan_(CH 1 to 5 ).pdf")
    text = ""
    for page in reader.pages:
        text += page.extract_text() + "\n"
    with open("pdf_content.txt", "w", encoding="utf-8") as f:
        f.write(text)
    print("Done pypdf")
except Exception as e:
    print("pypdf error:", e)
    try:
        from PyPDF2 import PdfReader
        reader = PdfReader("BCS223148_Ehsan_(CH 1 to 5 ).pdf")
        text = ""
        for page in reader.pages:
            text += page.extract_text() + "\n"
        with open("pdf_content.txt", "w", encoding="utf-8") as f:
            f.write(text)
        print("Done PyPDF2")
    except Exception as e2:
        print("PyPDF2 error:", e2)
        import os
        os.system("pip install PyPDF2")
        from PyPDF2 import PdfReader
        reader = PdfReader("BCS223148_Ehsan_(CH 1 to 5 ).pdf")
        text = ""
        for page in reader.pages:
            text += page.extract_text() + "\n"
        with open("pdf_content.txt", "w", encoding="utf-8") as f:
            f.write(text)
        print("Done PyPDF2 after install")
