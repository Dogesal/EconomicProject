const MONTHS = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const SHORT_MONTHS = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

/** `2026-05-15` -> Date local (evita el corrimiento de zona de `new Date(str)`). */
const parse = (isoDate) => {
    const [year, month, day] = String(isoDate).split('-').map(Number);

    return new Date(year, (month ?? 1) - 1, day ?? 1);
};

const startOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate());

/** Etiqueta corta para listas: «Hoy», «Ayer» o «15 May». */
export const dayLabel = (isoDate) => {
    if (!isoDate) {
        return '';
    }

    const date = parse(isoDate);
    const diffDays = Math.round((startOfDay(new Date()) - startOfDay(date)) / 86400000);

    if (diffDays === 0) {
        return 'Hoy';
    }

    if (diffDays === 1) {
        return 'Ayer';
    }

    return `${date.getDate()} ${SHORT_MONTHS[date.getMonth()]}`;
};

/** Encabezado de grupo: «MAYO 2026». */
export const monthLabel = (isoDate) => {
    const date = parse(isoDate);

    return `${MONTHS[date.getMonth()]} ${date.getFullYear()}`;
};

/** Clave estable de agrupación por mes. */
export const monthKey = (isoDate) => String(isoDate).slice(0, 7);
