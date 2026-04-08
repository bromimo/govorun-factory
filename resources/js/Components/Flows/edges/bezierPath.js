/**
 * Вычисление касательной на выходе из узла (вертикальная/горизонтальная по позиции хэндла).
 *
 * @param {{x: number, y: number}} from — точка выхода
 * @param {{x: number, y: number}} next — следующая точка (для расчёта магнитуды)
 * @param {string} position — позиция хэндла (top/bottom/left/right)
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
 * Вычисление касательной на входе в узел.
 *
 * @param {{x: number, y: number}} prev — предыдущая точка
 * @param {{x: number, y: number}} to — точка входа
 * @param {string} position — позиция хэндла (top/bottom/left/right)
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
 * Построение SVG path (Catmull-Rom сплайн → cubic bezier) через все точки.
 * Кривая проходит точно через каждый waypoint.
 * На выходе из source и входе в target — вертикальная касательная.
 *
 * @param {number} sx — X источника
 * @param {number} sy — Y источника
 * @param {number} tx — X цели
 * @param {number} ty — Y цели
 * @param {Array<{x: number, y: number}>} waypoints — промежуточные точки
 * @param {string} sourcePosition — позиция хэндла источника
 * @param {string} targetPosition — позиция хэндла цели
 * @returns {string} SVG path string
 */
export function buildBezierPath(sx, sy, tx, ty, waypoints, sourcePosition, targetPosition) {
    const pts = [{ x: sx, y: sy }, ...waypoints, { x: tx, y: ty }];

    const tangents = pts.map((p, i) => {
        if (i === 0) return exitTangent(pts[0], pts[1], sourcePosition);
        if (i === pts.length - 1) return entryTangent(pts[pts.length - 2], pts[pts.length - 1], targetPosition);
        return { x: (pts[i + 1].x - pts[i - 1].x) / 2, y: (pts[i + 1].y - pts[i - 1].y) / 2 };
    });

    let d = `M ${pts[0].x},${pts[0].y}`;
    for (let i = 0; i < pts.length - 1; i++) {
        const cp1x = pts[i].x + tangents[i].x / 3;
        const cp1y = pts[i].y + tangents[i].y / 3;
        const cp2x = pts[i + 1].x - tangents[i + 1].x / 3;
        const cp2y = pts[i + 1].y - tangents[i + 1].y / 3;
        d += ` C ${cp1x},${cp1y} ${cp2x},${cp2y} ${pts[i + 1].x},${pts[i + 1].y}`;
    }
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
