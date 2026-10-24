import { getSafeInteger } from "./numbers";

/**
 * Seconds of each time interval
 */
export const TIME_INTERVALS = {
    years: 60 * 60 * 24 * 365,
    months: 60 * 60 * 24 * 30,
    weeks: 60 * 60 * 24 * 7,
    days: 60 * 60 * 24,
    hours: 60 * 60,
    minutes: 60,
    seconds: 1,
};

export type TimeInterval = keyof typeof TIME_INTERVALS;

export type TimeIntervals = Partial<Record<TimeInterval, number>>;

/**
 * Split a number of seconds into time intervals
 *
 * @param intervalNames Intervals to split the seconds into
 */
export function secondsToIntervals(seconds: number, intervalNames: TimeInterval[] = Object.keys(TIME_INTERVALS) as TimeInterval[]) {
    const intervals: TimeIntervals = {};
    seconds = getSafeInteger(seconds);
    (Object.keys(TIME_INTERVALS) as TimeInterval[]).forEach((t) => {
        if (intervalNames.includes(t)) {
            intervals[t] = Math.floor(seconds / TIME_INTERVALS[t]);
            seconds -= intervals[t] * TIME_INTERVALS[t];
        }
    });
    return intervals;
}

/**
 * Get the number of seconds of time intervals
 */
export function intervalsToSeconds(intervals: TimeIntervals) {
    let seconds = 0;
    (Object.entries(intervals) as [TimeInterval, number][]).forEach(([interval, value]) => {
        seconds += value * TIME_INTERVALS[interval];
    });
    return getSafeInteger(seconds);
}
