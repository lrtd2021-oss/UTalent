#!/usr/bin/env python3
"""Generador de documentacion/UTalent_Documentacion.odt

Un unico documento que se arma en secciones: caratula, indice y luego una
seccion por cada parte del proyecto que se va cerrando. Para agregar una parte
nueva: escribir una funcion seccion_xxx(doc, estilos) que agregue su
contenido, y llamarla desde main() en el orden en que va apareciendo en el
documento. Correr este script regenera el .odt completo desde cero.

Uso:
    python build_doc.py
"""
import os
import subprocess
import sys

from odf.opendocument import OpenDocumentText
from odf.style import (
    Style, TextProperties, ParagraphProperties, GraphicProperties,
)
from odf.text import (
    H, P, Span, LineBreak,
    TableOfContent, TableOfContentSource, TableOfContentEntryTemplate,
    IndexTitleTemplate, IndexBody, IndexTitle,
    IndexEntryChapter, IndexEntryText, IndexEntryTabStop, IndexEntryPageNumber,
)
from odf.draw import Frame, Image as DrawImage

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
IMG_DIR = os.path.join(BASE_DIR, "img")
OUTPUT = os.path.join(BASE_DIR, "UTalent_Documentacion.odt")

# --- Datos de caratula: editar aca si cambian ---
ESTUDIANTE = "Lucas Techera"
CEDULA = "4.999.390-1"
PROYECTO = "UTalent"
SUBTITULO = ("Buscador de oportunidades laborales en Tecnologias de la "
             "Informacion para estudiantes y egresados de UTU")
INSTITUCION = "Esc. Tecnica Rocha — ANEP, Consejo de Educacion Tecnico Profesional (UTU)"
CURSO = "3.° BT Tecnologias de la Informacion"
ASIGNATURA = "Programacion Full-Stack"
TRABAJO = "Cuarto Practico — 2026"
FECHA = "Rocha, setiembre de 2026"


# --------------------------------------------------------------------------
# Estilos
# --------------------------------------------------------------------------

def build_styles(doc):
    """Crea los estilos de parrafo usados en el documento y los devuelve
    en un diccionario para referenciarlos por nombre corto."""
    estilos = {}

    def nuevo(nombre, align=None, size=None, weight=None, italic=None,
              margin_top=None, break_before=None, color=None):
        s = Style(name=nombre, family="paragraph")
        pp = {}
        if align:
            pp["textalign"] = align
        if margin_top:
            pp["margintop"] = margin_top
        if break_before:
            pp["breakbefore"] = break_before
        if pp:
            s.addElement(ParagraphProperties(**pp))
        tp = {}
        if size:
            tp["fontsize"] = size
        if weight:
            tp["fontweight"] = weight
        if italic:
            tp["fontstyle"] = "italic"
        if color:
            tp["color"] = color
        if tp:
            s.addElement(TextProperties(**tp))
        doc.styles.addElement(s)
        estilos[nombre] = s
        return s

    nuevo("CaratulaInstitucion", align="center", size="14pt", weight="bold")
    nuevo("CaratulaCurso", align="center", size="12pt")
    nuevo("CaratulaProyecto", align="center", size="30pt", weight="bold",
          margin_top="5cm")
    nuevo("CaratulaSubtitulo", align="center", size="13pt", italic=True,
          margin_top="0.4cm")
    nuevo("CaratulaDato", align="center", size="12pt", margin_top="6cm")
    nuevo("CaratulaDatoChico", align="center", size="11pt")
    nuevo("Centrado", align="center")
    nuevo("PiePagina", align="center", size="9pt", color="#666666")
    nuevo("PrimeraDeSeccion", break_before="page")

    return estilos


# --------------------------------------------------------------------------
# Caratula e indice
# --------------------------------------------------------------------------

def seccion_caratula(doc, estilos):
    doc.text.addElement(P(text=""))
    doc.text.addElement(P(stylename=estilos["CaratulaInstitucion"], text=INSTITUCION))
    doc.text.addElement(P(stylename=estilos["CaratulaCurso"], text=CURSO))
    doc.text.addElement(P(stylename=estilos["CaratulaCurso"], text=ASIGNATURA))

    p = P(stylename=estilos["CaratulaProyecto"], text=PROYECTO)
    doc.text.addElement(p)
    doc.text.addElement(P(stylename=estilos["CaratulaSubtitulo"], text=SUBTITULO))

    p = P(stylename=estilos["CaratulaDato"])
    p.addElement(Span(text=TRABAJO))
    p.addElement(LineBreak())
    p.addElement(Span(text=f"Estudiante: {ESTUDIANTE} (C.I. {CEDULA})"))
    p.addElement(LineBreak())
    p.addElement(Span(text=FECHA))
    doc.text.addElement(p)


def seccion_indice(doc, estilos):
    # Salto de pagina antes del indice
    doc.text.addElement(P(stylename=estilos["PrimeraDeSeccion"], text=""))

    toc = TableOfContent(name="Indice")
    toc_source = TableOfContentSource(outlinelevel=3)
    toc_source.addElement(IndexTitleTemplate(text="Indice"))

    # Plantilla de entrada para cada nivel de titulo: capitulo + texto + tab + pagina
    for nivel in (1, 2, 3):
        entry = TableOfContentEntryTemplate(outlinelevel=nivel, stylename=estilos["Centrado"])
        entry.addElement(IndexEntryChapter())
        entry.addElement(IndexEntryText())
        entry.addElement(IndexEntryTabStop(type="right", leaderchar="."))
        entry.addElement(IndexEntryPageNumber())
        toc_source.addElement(entry)

    toc.addElement(toc_source)

    index_body = IndexBody()
    title = IndexTitle(name="Titulo del indice")
    title.addElement(H(outlinelevel=1, text="Indice"))
    index_body.addElement(title)
    index_body.addElement(P(text=(
        "(Este indice se genera automaticamente a partir de los titulos del "
        "documento. Si se agregan secciones nuevas, actualizarlo en LibreOffice "
        "con clic derecho sobre el indice > Actualizar indice, o con la tecla F9.)"
    )))
    toc.addElement(index_body)

    doc.text.addElement(toc)


# --------------------------------------------------------------------------
# Ejercicio 1: propuesta, arquitectura y diagrama de clases
# --------------------------------------------------------------------------

def parrafo(doc, texto, estilo=None):
    doc.text.addElement(P(stylename=estilo, text=texto) if estilo else P(text=texto))


def item_lista(doc, texto):
    # Lista simple como parrafo con guion, para no depender de estilos de lista.
    doc.text.addElement(P(text=f"• {texto}"))


def seccion_propuesta_y_arquitectura(doc, estilos):
    doc.text.addElement(P(stylename=estilos["PrimeraDeSeccion"], text=""))
    doc.text.addElement(H(outlinelevel=1, text="1. Propuesta y arquitectura"))

    doc.text.addElement(H(outlinelevel=2, text="1.1 Proyecto elegido"))
    parrafo(doc,
        "Buscador de oportunidades laborales en Tecnologias de la Informacion "
        "dirigido a estudiantes y egresados tecnicos de UTU. El sistema permite "
        "buscar ofertas por titulo de puesto (por ejemplo, “Programador” o "
        "“Soporte Tecnico”) y filtrar por sector (publico/privado), visibilidad "
        "del salario, modalidad de postulacion y departamento, agregando "
        "resultados de distintas fuentes: el portal estatal Uruguay Concursa "
        "(empleo publico) y portales privados de empleo.")

    doc.text.addElement(H(outlinelevel=2, text="1.2 Eleccion del stack: Laravel"))
    parrafo(doc,
        "Se descarto PHP/SQL vanilla en favor de Laravel por los siguientes "
        "motivos:")
    item_lista(doc,
        "El foco del practico es la extraccion y consulta de datos, no la "
        "infraestructura. El proyecto necesita colas de trabajo (para "
        "actualizar ofertas en segundo plano sin bloquear al usuario), un "
        "scheduler (para relanzar el scraping periodicamente), un cliente "
        "HTTP para invocar un modelo de lenguaje, y un ORM para modelar "
        "relaciones entre fuentes, ofertas y sinonimos de busqueda. Laravel "
        "provee todo esto de fabrica (Queues, Scheduler, Http::, Eloquent), "
        "dejando el tiempo disponible para la logica de scraping y "
        "normalizacion, que es el objetivo pedagogico real.")
    item_lista(doc,
        "Consistencia de convenciones: al tener varias fuentes de datos "
        "heterogeneas, conviene una estructura de carpetas y convenciones ya "
        "establecidas en vez de definirlas desde cero.")
    item_lista(doc,
        "Valor de aprendizaje adicional: el grupo ya cuenta con experiencia "
        "en PHP/SQL vanilla en otro proyecto, por lo que este practico suma "
        "un framework ampliamente usado en la industria, en linea con el "
        "aporte al proyecto de egreso que pide el ejercicio 3.")

    doc.text.addElement(H(outlinelevel=2, text="1.3 Patron de arquitectura: MVC + Strategy + Repository"))
    parrafo(doc,
        "El proyecto usa el MVC que Laravel impone por convencion (rutas → "
        "controladores → modelos Eloquent → vistas Blade) como esqueleto "
        "general, pero el MVC puro no resuelve bien un problema central de "
        "este proyecto: obtener datos de fuentes externas heterogeneas y "
        "cambiantes sin acoplar esa logica al resto de la aplicacion. Por eso "
        "se suman dos patrones adicionales:")
    item_lista(doc,
        "Strategy: cada fuente de empleo (Uruguay Concursa, portal privado A, "
        "portal privado B) implementa una interfaz comun "
        "FuenteEmpleoInterface. Agregar, quitar o reparar una fuente no "
        "requiere tocar el resto del sistema (principio abierto/cerrado): si "
        "un sitio cambia su HTML, solo se ajusta su estrategia concreta.")
    item_lista(doc,
        "Repository: el acceso a la persistencia (ofertas, fuentes, "
        "sinonimos) se aisla detras de interfaces "
        "(OfertaRepositoryInterface, etc.), separando de donde vienen los "
        "datos scrapeados de como se guardan y consultan localmente. Esto "
        "tambien permite que la busqueda no dependa de scrapear en vivo en "
        "cada request: los resultados se persisten y refrescan por jobs en "
        "segundo plano, haciendo el sistema tolerante a que una fuente falle "
        "o cambie de estructura.")
    item_lista(doc,
        "Un Service (BuscadorService) orquesta las estrategias, delega la "
        "normalizacion de texto libre a un servicio de IA (NormalizadorIA) y "
        "persiste via los repositorios.")

    doc.text.addElement(H(outlinelevel=2, text="1.4 Diagrama de clases"))
    agregar_imagen(doc, os.path.join(IMG_DIR, "diagrama_clases.png"),
                    ancho_cm=17, alto_cm=10.7,
                    pie="Diagrama de clases: interfaces de fuentes (Strategy), "
                        "servicio orquestador, normalizador de IA, "
                        "repositorios (Repository) y modelos.")

    parrafo(doc,
        "FuenteEmpleoInterface y sus implementaciones son el punto de "
        "extension: sumar un portal nuevo es crear una clase mas, sin tocar "
        "BuscadorService. NormalizadorIA es el unico punto de contacto con "
        "el modelo de lenguaje (documentado en la seccion de uso de IA): "
        "recibe el texto crudo de una oferta y devuelve campos estructurados "
        "(salario, seniority, tecnologias). Los tres repositorios (Oferta, "
        "Fuente, Sinonimo) son el ABM real del sistema, reemplazando el CRUD "
        "de usuarios que la consigna explicitamente no pide para esta "
        "opcion: se administra el propio motor de busqueda (fuentes activas "
        "y sinonimos de busqueda) en lugar de cuentas de usuario. "
        "ActualizarOfertasJob corre en segundo plano (cola y scheduler de "
        "Laravel), evitando scrapear en vivo en cada busqueda del usuario y "
        "aislando fallas de una fuente puntual.")


def agregar_imagen(doc, ruta, ancho_cm, alto_cm, pie=None):
    href = doc.addPicture(ruta)
    frame = Frame(width=f"{ancho_cm}cm", height=f"{alto_cm}cm",
                  anchortype="paragraph")
    frame.addElement(DrawImage(href=href))
    p = P(stylename="Centrado")
    p.addElement(frame)
    doc.text.addElement(p)
    if pie:
        doc.text.addElement(P(stylename="PiePagina", text=pie))


# --------------------------------------------------------------------------
# Main
# --------------------------------------------------------------------------

def main():
    doc = OpenDocumentText()
    estilos = build_styles(doc)

    seccion_caratula(doc, estilos)
    seccion_indice(doc, estilos)
    seccion_propuesta_y_arquitectura(doc, estilos)

    doc.save(OUTPUT)
    print(f"Generado: {OUTPUT}")

    # El indice (tabla de contenidos) se arma vacio con odfpy: para que
    # LibreOffice calcule los numeros de pagina hay que abrirlo y forzar un
    # refresco. Se hace con el propio Python de LibreOffice (trae el modulo
    # `uno`, el Python del sistema no lo tiene).
    lo_python = r"C:\Program Files\LibreOffice\program\python.exe"
    actualizar = os.path.join(BASE_DIR, "actualizar_indice.py")
    args = [lo_python, actualizar] + (["--pdf"] if "--pdf" in sys.argv else [])
    subprocess.run(args, check=True)


if __name__ == "__main__":
    main()
