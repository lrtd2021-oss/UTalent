#!/usr/bin/env python3
"""Abre el .odt generado con LibreOffice (headless, via UNO), fuerza la
actualizacion del indice (tabla de contenidos) contra los titulos actuales,
lo guarda y de paso exporta un PDF para revisar visualmente.

Se ejecuta con el interprete de Python que trae LibreOffice (tiene el modulo
`uno`), no con el Python del sistema:

    "C:\\Program Files\\LibreOffice\\program\\python.exe" actualizar_indice.py

build_doc.py lo invoca automaticamente al final.
"""
import os
import subprocess
import sys
import time

import uno
from com.sun.star.beans import PropertyValue

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
ODT_PATH = os.path.join(BASE_DIR, "UTalent_Documentacion.odt")
PDF_PATH = os.path.join(BASE_DIR, "UTalent_Documentacion.pdf")
SOFFICE = r"C:\Program Files\LibreOffice\program\soffice.exe"
PORT = 2002


def prop(name, value):
    p = PropertyValue()
    p.Name = name
    p.Value = value
    return p


def connect(local_ctx, tries=30):
    resolver = local_ctx.ServiceManager.createInstanceWithContext(
        "com.sun.star.bridge.UnoUrlResolver", local_ctx)
    url = (f"uno:socket,host=localhost,port={PORT};urp;"
           "StarOffice.ComponentContext")
    for _ in range(tries):
        try:
            return resolver.resolve(url)
        except Exception:
            time.sleep(1)
    raise RuntimeError("No se pudo conectar a soffice headless")


def main():
    export_pdf = "--pdf" in sys.argv
    soffice_proc = subprocess.Popen([
        SOFFICE, "--headless", "--invisible", "--nocrashreport",
        "--nodefault", "--norestore", "--nologo", "--nofirststartwizard",
        f"--accept=socket,host=localhost,port={PORT};urp;",
    ])
    try:
        local_ctx = uno.getComponentContext()
        remote_ctx = connect(local_ctx)
        smgr = remote_ctx.ServiceManager
        desktop = smgr.createInstanceWithContext("com.sun.star.frame.Desktop", remote_ctx)

        url = uno.systemPathToFileUrl(ODT_PATH)
        doc = desktop.loadComponentFromURL(
            url, "_blank", 0, (prop("Hidden", True),))

        indexes = doc.getDocumentIndexes()
        for i in range(indexes.getCount()):
            indexes.getByIndex(i).update()

        doc.store()

        if export_pdf:
            pdf_url = uno.systemPathToFileUrl(PDF_PATH)
            doc.storeToURL(pdf_url, (prop("FilterName", "writer_pdf_Export"),))

        doc.close(False)
        msg = "Indice actualizado y .odt guardado"
        msg += " (+ .pdf de revision)." if export_pdf else "."
        print(msg)
    finally:
        try:
            desktop.terminate()
        except Exception:
            pass
        soffice_proc.wait(timeout=15)


if __name__ == "__main__":
    main()
