import React from 'react';

const STATUS_CONFIG = {
    draft: {
        label: 'Draft',
        bg: 'bg-gray-100',
        text: 'text-gray-700',
        dot: 'bg-gray-400',
    },
    approved: {
        label: 'Approved',
        bg: 'bg-blue-100',
        text: 'text-blue-700',
        dot: 'bg-blue-400',
    },
    partially_collected: {
        label: 'Partially Collected',
        bg: 'bg-yellow-100',
        text: 'text-yellow-700',
        dot: 'bg-yellow-400',
    },
    collected: {
        label: 'Collected',
        bg: 'bg-green-100',
        text: 'text-green-700',
        dot: 'bg-green-400',
    },
    cancelled: {
        label: 'Cancelled',
        bg: 'bg-red-100',
        text: 'text-red-700',
        dot: 'bg-red-400',
    },
};

export default function StatusBadge({ status }) {
    const statusKey = typeof status === 'object' ? status?.value : status;
    const config = STATUS_CONFIG[statusKey] || STATUS_CONFIG.draft;

    return (
        <span
            className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold ${config.bg} ${config.text}`}
        >
            <span className={`w-1.5 h-1.5 rounded-full ${config.dot}`} />
            {config.label}
        </span>
    );
}
