/**
 * Построение SVG path из source/target и массива управляющих точек (waypoints).
 *
 * @param {number} sx — X источника
 * @param {number} sy — Y источника
 * @param {number} tx — X цели
 * @param {number} ty — Y цели
 * @param {Array<{x: number, y: number}>} waypoints — управляющие точки bezier
 * @returns {string} SVG path string
 */
export function buildBezierPath(sx, sy, tx, ty, waypoints) {
    if (!waypoints?.length) {
        const midY = (sy + ty) / 2;
        return `M ${sx},${sy} C ${sx},${midY} ${tx},${midY} ${tx},${ty}`;
    }

    const pts = [{ x: sx, y: sy }, ...waypoints, { x: tx, y: ty }];

    if (pts.length === 3) {
        return `M ${pts[0].x},${pts[0].y} Q ${pts[1].x},${pts[1].y} ${pts[2].x},${pts[2].y}`;
    }

    let d = `M ${pts[0].x},${pts[0].y}`;

    // Первый сегмент: Q wp1, midpoint(wp1, wp2)
    const mid1x = (pts[1].x + pts[2].x) / 2;
    const mid1y = (pts[1].y + pts[2].y) / 2;
    d += ` Q ${pts[1].x},${pts[1].y} ${mid1x},${mid1y}`;

    // Средние сегменты
    for (let i = 2; i < pts.length - 2; i++) {
        const mx = (pts[i].x + pts[i + 1].x) / 2;
        const my = (pts[i].y + pts[i + 1].y) / 2;
        d += ` Q ${pts[i].x},${pts[i].y} ${mx},${my}`;
    }

    // Последний сегмент: Q wpN, target
    const last = pts.length - 1;
    d += ` Q ${pts[last - 1].x},${pts[last - 1].y} ${pts[last].x},${pts[last].y}`;

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
