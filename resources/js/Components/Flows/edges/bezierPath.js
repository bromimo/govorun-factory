/** Длина прямого вертикального/горизонтального участка на выходе/входе из узла (= шаг сетки). */
const STEM = 16;

/**
 * Смещение stem-точки от хэндла по направлению позиции.
 *
 * @param {string} position — top/bottom/left/right
 * @returns {{x: number, y: number}}
 */
function stemOffset(position) {
    switch (position) {
        case 'top': return { x: 0, y: -STEM };
        case 'bottom': return { x: 0, y: STEM };
        case 'left': return { x: -STEM, y: 0 };
        case 'right': return { x: STEM, y: 0 };
        default: return { x: 0, y: STEM };
    }
}

/**
 * Касательная на выходе stem → первая точка кривой.
 *
 * @param {{x: number, y: number}} from
 * @param {{x: number, y: number}} next
 * @param {string} position
 * @returns {{x: number, y: number}}
 */
function exitTangent(from, next, position) {
    const mag = Math.max(Math.hypot(next.x - from.x, next.y - from.y) * 0.5, 30);
    switch (position) {
        case 'top': return { x: 0, y: -mag };
        case 'bottom': return { x: 0, y: mag };
        case 'left': return { x: -mag, y: 0 };
        case 'right': return { x: mag, y: 0 };
        default: return { x: 0, y: mag };
    }
}

/**
 * Касательная на входе в stem-точку цели.
 *
 * @param {{x: number, y: number}} prev
 * @param {{x: number, y: number}} to
 * @param {string} position
 * @returns {{x: number, y: number}}
 */
function entryTangent(prev, to, position) {
    const mag = Math.max(Math.hypot(to.x - prev.x, to.y - prev.y) * 0.5, 30);
    switch (position) {
        case 'top': return { x: 0, y: mag };
        case 'bottom': return { x: 0, y: -mag };
        case 'left': return { x: mag, y: 0 };
        case 'right': return { x: -mag, y: 0 };
        default: return { x: 0, y: mag };
    }
}

/**
 * Построение SVG path: прямой stem от узла + Catmull-Rom кривая через waypoints + stem в узел.
 * Гарантирует видимый прямой участок длиной STEM на выходе/входе.
 *
 * @param {number} sx — X источника
 * @param {number} sy — Y источника
 * @param {number} tx — X цели
 * @param {number} ty — Y цели
 * @param {Array<{x: number, y: number}>} waypoints — промежуточные точки (абсолютные координаты)
 * @param {string} sourcePosition — позиция хэндла источника
 * @param {string} targetPosition — позиция хэндла цели
 * @returns {string} SVG path string
 */
export function buildBezierPath(sx, sy, tx, ty, waypoints, sourcePosition, targetPosition) {
    const sOff = stemOffset(sourcePosition);
    const tOff = stemOffset(targetPosition);

    const stemStart = { x: sx + sOff.x, y: sy + sOff.y };
    const stemEnd = { x: tx + tOff.x, y: ty + tOff.y };

    const pts = [stemStart, ...(waypoints || []), stemEnd];

    const tangents = pts.map((p, i) => {
        if (i === 0) return exitTangent(pts[0], pts[1], sourcePosition);
        if (i === pts.length - 1) return entryTangent(pts[pts.length - 2], pts[pts.length - 1], targetPosition);
        return { x: (pts[i + 1].x - pts[i - 1].x) / 2, y: (pts[i + 1].y - pts[i - 1].y) / 2 };
    });

    let d = `M ${sx},${sy} L ${stemStart.x},${stemStart.y}`;
    for (let i = 0; i < pts.length - 1; i++) {
        const cp1x = pts[i].x + tangents[i].x / 3;
        const cp1y = pts[i].y + tangents[i].y / 3;
        const cp2x = pts[i + 1].x - tangents[i + 1].x / 3;
        const cp2y = pts[i + 1].y - tangents[i + 1].y / 3;
        d += ` C ${cp1x},${cp1y} ${cp2x},${cp2y} ${pts[i + 1].x},${pts[i + 1].y}`;
    }
    d += ` L ${tx},${ty}`;
    return d;
}

/**
 * Найти ближайший сегмент path к точке клика для вставки waypoint.
 *
 * @param {number} sx
 * @param {number} sy
 * @param {number} tx
 * @param {number} ty
 * @param {Array<{x: number, y: number}>} waypoints
 * @param {{x: number, y: number}} click — точка клика в flow-координатах
 * @returns {number} индекс в массиве waypoints, куда вставить новую точку
 */
export function findInsertIndex(sx, sy, tx, ty, waypoints, click) {
    const pts = [{ x: sx, y: sy }, ...(waypoints || []), { x: tx, y: ty }];

    let bestIdx = 0;
    let bestDist = Infinity;

    for (let i = 0; i < pts.length - 1; i++) {
        const mx = (pts[i].x + pts[i + 1].x) / 2;
        const my = (pts[i].y + pts[i + 1].y) / 2;
        const dist = Math.hypot(click.x - mx, click.y - my);
        if (dist < bestDist) {
            bestDist = dist;
            bestIdx = i;
        }
    }

    return bestIdx;
}
