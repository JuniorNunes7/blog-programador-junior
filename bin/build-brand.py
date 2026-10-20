"""Export the licensed font sources as WOFF2 and portable brand outlines.

Optional authoring tool: python -m pip install 'fonttools[woff]'.
Normal asset builds consume the checked-in SVG outlines and need only npm.
"""
from pathlib import Path
from fontTools.ttLib import TTFont
from fontTools.varLib.instancer import instantiateVariableFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools import subset

ROOT = Path(__file__).resolve().parent.parent
FONTS = ROOT / 'resources/fonts'
PUBLIC = ROOT / 'public/assets'
SOURCES = ROOT / 'resources/brand'
OUTLINE_FONTS = {}


def font(name, weight):
    face = TTFont(FONTS / f'{name}.ttf')
    if 'fvar' in face:
        face = instantiateVariableFont(face, {'wght': weight}, inplace=True)
    return face


def outlines(text, name, weight, size, x, y, color):
    if (name, weight) not in OUTLINE_FONTS:
        OUTLINE_FONTS[(name, weight)] = font(name, weight)
    face = OUTLINE_FONTS[(name, weight)]
    glyphs = face.getGlyphSet()
    cmap = face.getBestCmap()
    scale = size / face['head'].unitsPerEm
    parts = []
    for letter in text:
        glyph = glyphs[cmap[ord(letter)]]
        pen = SVGPathPen(glyphs)
        glyph.draw(pen)
        parts.append(f'<path fill="{color}" transform="translate({x:.3f} {y}) scale({scale:.6f} {-scale:.6f})" d="{pen.getCommands()}"/>')
        x += glyph.width * scale
    return ''.join(parts), x


def svg(width, height, content):
    return f'<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" viewBox="0 0 {width} {height}">{content}</svg>'


for name, weights in [('manrope', [500, 800]), ('source-sans-3', [400, 600]), ('ibm-plex-mono', [400])]:
    for weight in weights:
        face = font(name, weight)
        options = subset.Options()
        options.flavor = 'woff2'
        sub = subset.Subsetter(options=options)
        sub.populate(unicodes=list(range(0x20, 0x0250)) + list(range(0x2000, 0x2070)) + [0x20ac, 0x2190, 0x2191, 0x2192, 0x2193, 0x2197, 0x2212])
        sub.subset(face)
        face.flavor = 'woff2'
        face.save(PUBLIC / 'fonts' / f'{name}-{weight}.woff2')

for theme, bg, text, brand, accent in [('light', '#F6F1E7', '#252724', '#245C53', '#B84B2D'), ('dark', '#1C1F1D', '#E8E4DA', '#91B5A5', '#D58B70')]:
    first, x = outlines('programador ', 'manrope', 500, 40, 20, 58, text)
    second, x = outlines('junior', 'manrope', 800, 40, x, 58, brand)
    cursor, x = outlines('_', 'manrope', 800, 40, x, 58, accent)
    (PUBLIC / 'brand' / f'logo-horizontal-{theme}.svg').write_text(svg(round(x + 20), 88, first + second + cursor))
    first, _ = outlines('programador', 'manrope', 500, 48, 24, 70, text)
    second, x = outlines('junior', 'manrope', 800, 72, 24, 145, brand)
    cursor, _ = outlines('_', 'manrope', 800, 72, x, 145, accent)
    (PUBLIC / 'brand' / f'logo-stacked-{theme}.svg').write_text(svg(360, 180, first + second + cursor))
    mark, x = outlines('pj', 'manrope', 800, 480, 190, 660, text)
    cursor, _ = outlines('_', 'manrope', 800, 320, x + 10, 660, accent)
    icon = svg(1024, 1024, f'<rect width="1024" height="1024" rx="190" fill="{bg}"/>' + mark + cursor)
    (PUBLIC / 'brand' / f'monogram-{theme}.svg').write_text(icon)
    first, _ = outlines('programador', 'manrope', 500, 76, 80, 240, text)
    second, x = outlines('junior', 'manrope', 800, 110, 80, 352, brand)
    cursor, _ = outlines('_', 'manrope', 800, 110, x, 352, accent)
    tagline, _ = outlines('Menos ego. Mais aprendizado.', 'source-sans-3', 400, 34, 84, 430, text)
    (SOURCES / f'banner-type-{theme}.svg').write_text(svg(1200, 630, first + second + cursor + tagline))

(PUBLIC / 'brand/favicon.svg').write_text((PUBLIC / 'brand/monogram-dark.svg').read_text())
print('WOFF2 fonts and outlined brand SVGs exported.')
