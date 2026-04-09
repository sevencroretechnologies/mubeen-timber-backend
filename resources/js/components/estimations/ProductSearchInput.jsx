import React, { useState, useRef, useEffect } from 'react';

export default function ProductSearchInput({
    products,
    selectedProductId,
    selectedProductName,
    onSelect,
    onCreateNew,
}) {
    const [searchTerm, setSearchTerm] = useState('');
    const [showDropdown, setShowDropdown] = useState(false);
    const [creating, setCreating] = useState(false);
    const wrapperRef = useRef(null);

    const filteredProducts = products.filter((p) =>
        p.name.toLowerCase().includes(searchTerm.toLowerCase())
    );

    // Close dropdown on outside click
    useEffect(() => {
        function handleClickOutside(event) {
            if (wrapperRef.current && !wrapperRef.current.contains(event.target)) {
                setShowDropdown(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const handleSelect = (product) => {
        onSelect(product.id, product.name);
        setSearchTerm('');
        setShowDropdown(false);
    };

    const handleCreateNew = async () => {
        if (!searchTerm.trim()) return;
        setCreating(true);
        try {
            await onCreateNew(searchTerm.trim());
            setSearchTerm('');
            setShowDropdown(false);
        } finally {
            setCreating(false);
        }
    };

    const handleClear = () => {
        onSelect(null, '');
        setSearchTerm('');
    };

    return (
        <div ref={wrapperRef} className="relative">
            {selectedProductId ? (
                <div className="flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg bg-white">
                    <span className="flex-1 text-sm text-gray-800 truncate">
                        {selectedProductName}
                    </span>
                    <button
                        type="button"
                        onClick={handleClear}
                        className="text-gray-400 hover:text-gray-600 flex-shrink-0"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            ) : (
                <input
                    type="text"
                    placeholder="Search product..."
                    value={searchTerm}
                    onChange={(e) => {
                        setSearchTerm(e.target.value);
                        setShowDropdown(true);
                    }}
                    onFocus={() => setShowDropdown(true)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                />
            )}

            {showDropdown && !selectedProductId && (
                <div className="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                    {filteredProducts.length > 0 ? (
                        filteredProducts.map((p) => (
                            <button
                                key={p.id}
                                type="button"
                                onClick={() => handleSelect(p)}
                                className="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 hover:text-blue-700 transition-colors border-b border-gray-50 last:border-b-0"
                            >
                                {p.name}
                            </button>
                        ))
                    ) : searchTerm.trim() ? (
                        <div className="px-3 py-2 text-sm text-gray-500">
                            No products found
                        </div>
                    ) : (
                        <div className="px-3 py-2 text-sm text-gray-400">
                            Type to search products...
                        </div>
                    )}

                    {searchTerm.trim() && (
                        <button
                            type="button"
                            onClick={handleCreateNew}
                            disabled={creating}
                            className="w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 transition-colors border-t border-gray-100 font-medium"
                        >
                            {creating ? (
                                'Creating...'
                            ) : (
                                <>
                                    <span className="mr-1">+</span> Create &quot;{searchTerm.trim()}&quot;
                                </>
                            )}
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}
