import React, { useState, useEffect, useRef } from 'react';
import { estimationsApi, customersApi, projectsApi, productApi } from '../../services/api';
import StatusBadge from './StatusBadge';
import ProductSearchInput from './ProductSearchInput';

export default function EstimationForm({ estimationId = null, onSuccess = null }) {
    const [formData, setFormData] = useState({
        customer_id: null,
        project_id: null,
        description: '',
    });

    const [items, setItems] = useState([
        { product_id: null, product_name: '', description: '', quantity: 1 },
    ]);

    const [customers, setCustomers] = useState([]);
    const [projects, setProjects] = useState([]);
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});
    const [status, setStatus] = useState('draft');
    const [successMessage, setSuccessMessage] = useState('');

    // Load initial data
    useEffect(() => {
        fetchCustomers();
        fetchProducts();
    }, []);

    // Fetch projects when customer changes
    useEffect(() => {
        if (formData.customer_id) {
            fetchProjects(formData.customer_id);
        } else {
            setProjects([]);
            setFormData((prev) => ({ ...prev, project_id: null }));
        }
    }, [formData.customer_id]);

    // Load estimation data if editing
    useEffect(() => {
        if (estimationId) {
            loadEstimation(estimationId);
        }
    }, [estimationId]);

    const fetchCustomers = async () => {
        try {
            const res = await customersApi.list({ per_page: 100 });
            const data = res.data?.data?.data || res.data?.data || res.data || [];
            setCustomers(Array.isArray(data) ? data : []);
        } catch (err) {
            console.error('Failed to load customers:', err);
        }
    };

    const fetchProjects = async (customerId) => {
        try {
            const res = await projectsApi.list({ customer_id: customerId, per_page: 100 });
            const data = res.data?.data?.data || res.data?.data || res.data || [];
            setProjects(Array.isArray(data) ? data : []);
        } catch (err) {
            console.error('Failed to load projects:', err);
        }
    };

    const fetchProducts = async () => {
        try {
            const res = await productApi.list({ per_page: 100 });
            const data = res.data?.data?.data || res.data?.data || res.data || [];
            setProducts(Array.isArray(data) ? data : []);
        } catch (err) {
            console.error('Failed to load products:', err);
        }
    };

    const loadEstimation = async (id) => {
        setLoading(true);
        try {
            const res = await estimationsApi.show(id);
            const est = res.data;
            setFormData({
                customer_id: est.customer_id,
                project_id: est.project_id,
                description: est.description || '',
            });
            setStatus(est.status?.value || est.status || 'draft');
            if (est.product) {
                setItems([
                    {
                        product_id: est.product_id,
                        product_name: est.product?.name || '',
                        description: est.description || '',
                        quantity: est.quantity || 1,
                    },
                ]);
            }
        } catch (err) {
            console.error('Failed to load estimation:', err);
        } finally {
            setLoading(false);
        }
    };

    const updateItem = (index, field, value) => {
        setItems((prev) => {
            const updated = [...prev];
            updated[index] = { ...updated[index], [field]: value };
            return updated;
        });
    };

    const addRow = () => {
        setItems((prev) => [
            ...prev,
            { product_id: null, product_name: '', description: '', quantity: 1 },
        ]);
    };

    const removeRow = (index) => {
        if (items.length <= 1) return;
        setItems((prev) => prev.filter((_, i) => i !== index));
    };

    const createProductInline = async (name, index) => {
        try {
            const res = await productApi.create({ name });
            const product = res.data?.data || res.data;
            setProducts((prev) => [...prev, product]);
            updateItem(index, 'product_id', product.id);
            updateItem(index, 'product_name', product.name);
            return product;
        } catch (err) {
            console.error('Failed to create product:', err);
            return null;
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setErrors({});
        setSuccessMessage('');
        setSaving(true);

        const payload = {
            ...formData,
            project_id: formData.project_id ?? null,
            items: items.map((item) => ({
                product_id: item.product_id,
                product_name: !item.product_id ? item.product_name : undefined,
                description: item.description || undefined,
                quantity: item.quantity || 1,
            })),
        };

        try {
            if (estimationId) {
                await estimationsApi.update(estimationId, payload);
                setSuccessMessage('Estimation updated successfully!');
            } else {
                await estimationsApi.create(payload);
                setSuccessMessage('Estimation created successfully!');
                // Reset form
                setFormData({ customer_id: null, project_id: null, description: '' });
                setItems([{ product_id: null, product_name: '', description: '', quantity: 1 }]);
            }
            if (onSuccess) onSuccess();
        } catch (err) {
            if (err.response?.status === 422) {
                setErrors(err.response.data.errors || {});
            } else {
                setErrors({ general: [err.response?.data?.error || 'An error occurred'] });
            }
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center p-8">
                <div className="text-gray-500">Loading estimation...</div>
            </div>
        );
    }

    return (
        <div className="max-w-4xl mx-auto">
            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">
                    {estimationId ? 'Edit Estimation' : 'New Estimation'}
                </h2>
                {status && <StatusBadge status={status} />}
            </div>

            {/* Success Message */}
            {successMessage && (
                <div className="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    {successMessage}
                </div>
            )}

            {/* Error Messages */}
            {errors.general && (
                <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                    {errors.general.join(', ')}
                </div>
            )}

            <form onSubmit={handleSubmit}>
                {/* Customer & Project Card */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 className="text-lg font-semibold text-gray-700 mb-4">Customer & Project</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {/* Customer Select */}
                        <div>
                            <label className="block text-sm font-medium text-gray-600 mb-1">
                                Customer <span className="text-red-500">*</span>
                            </label>
                            <select
                                value={formData.customer_id || ''}
                                onChange={(e) =>
                                    setFormData((prev) => ({
                                        ...prev,
                                        customer_id: e.target.value ? Number(e.target.value) : null,
                                    }))
                                }
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                required
                            >
                                <option value="">Select Customer</option>
                                {customers.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                            {errors.customer_id && (
                                <p className="text-red-500 text-xs mt-1">{errors.customer_id[0]}</p>
                            )}
                        </div>

                        {/* Project Select */}
                        <div>
                            <label className="block text-sm font-medium text-gray-600 mb-1">
                                Project
                            </label>
                            <select
                                value={formData.project_id || ''}
                                onChange={(e) =>
                                    setFormData((prev) => ({
                                        ...prev,
                                        project_id: e.target.value ? Number(e.target.value) : null,
                                    }))
                                }
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                disabled={!formData.customer_id}
                            >
                                <option value="">
                                    {formData.customer_id
                                        ? 'Select Project'
                                        : 'Select a customer first'}
                                </option>
                                {projects.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.name}
                                    </option>
                                ))}
                            </select>
                            {errors.project_id && (
                                <p className="text-red-500 text-xs mt-1">{errors.project_id[0]}</p>
                            )}
                        </div>
                    </div>

                    {/* Description */}
                    <div className="mt-4">
                        <label className="block text-sm font-medium text-gray-600 mb-1">
                            Description
                        </label>
                        <textarea
                            value={formData.description}
                            onChange={(e) =>
                                setFormData((prev) => ({ ...prev, description: e.target.value }))
                            }
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none"
                            rows={3}
                            placeholder="Enter estimation description..."
                        />
                    </div>
                </div>

                {/* Products Card */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-semibold text-gray-700">Products</h3>
                        <button
                            type="button"
                            onClick={addRow}
                            className="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors"
                        >
                            <span className="mr-1">+</span> Add Row
                        </button>
                    </div>

                    {/* Table Header */}
                    <div className="hidden md:grid md:grid-cols-12 gap-3 mb-2 px-2">
                        <div className="col-span-1 text-xs font-semibold text-gray-500 uppercase">
                            #
                        </div>
                        <div className="col-span-4 text-xs font-semibold text-gray-500 uppercase">
                            Product
                        </div>
                        <div className="col-span-4 text-xs font-semibold text-gray-500 uppercase">
                            Description
                        </div>
                        <div className="col-span-2 text-xs font-semibold text-gray-500 uppercase">
                            Qty
                        </div>
                        <div className="col-span-1 text-xs font-semibold text-gray-500 uppercase">
                            Action
                        </div>
                    </div>

                    {/* Table Rows */}
                    {items.map((item, index) => (
                        <div
                            key={index}
                            className="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3 p-3 bg-gray-50 rounded-lg border border-gray-100 items-start"
                        >
                            {/* Row Number */}
                            <div className="col-span-1 flex items-center">
                                <span className="text-sm font-medium text-gray-400">
                                    {index + 1}
                                </span>
                            </div>

                            {/* Product Search */}
                            <div className="col-span-4">
                                <ProductSearchInput
                                    products={products}
                                    selectedProductId={item.product_id}
                                    selectedProductName={item.product_name}
                                    onSelect={(productId, productName) => {
                                        updateItem(index, 'product_id', productId);
                                        updateItem(index, 'product_name', productName);
                                    }}
                                    onCreateNew={(name) => createProductInline(name, index)}
                                />
                                {errors[`items.${index}.product_id`] && (
                                    <p className="text-red-500 text-xs mt-1">
                                        {errors[`items.${index}.product_id`][0]}
                                    </p>
                                )}
                            </div>

                            {/* Description */}
                            <div className="col-span-4">
                                <input
                                    type="text"
                                    placeholder="Description"
                                    value={item.description}
                                    onChange={(e) =>
                                        updateItem(index, 'description', e.target.value)
                                    }
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                                />
                            </div>

                            {/* Quantity */}
                            <div className="col-span-2">
                                <input
                                    type="number"
                                    min="1"
                                    value={item.quantity}
                                    onChange={(e) =>
                                        updateItem(index, 'quantity', Number(e.target.value) || 1)
                                    }
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                                />
                            </div>

                            {/* Remove */}
                            <div className="col-span-1 flex items-center justify-center">
                                <button
                                    type="button"
                                    onClick={() => removeRow(index)}
                                    disabled={items.length <= 1}
                                    className="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                                    title="Remove row"
                                >
                                    <svg
                                        className="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    ))}

                    {/* Add Row Button (bottom) */}
                    <button
                        type="button"
                        onClick={addRow}
                        className="mt-2 w-full py-2 border-2 border-dashed border-gray-300 text-gray-400 rounded-lg hover:border-blue-400 hover:text-blue-500 transition-colors text-sm"
                    >
                        + Add another product
                    </button>
                </div>

                {/* Sticky Save Bar */}
                <div className="sticky bottom-0 bg-white border-t border-gray-200 p-4 -mx-4 px-4 flex items-center justify-between rounded-b-xl shadow-lg">
                    <div className="text-sm text-gray-500">
                        {items.length} product{items.length !== 1 ? 's' : ''} added
                    </div>
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => {
                                setFormData({
                                    customer_id: null,
                                    project_id: null,
                                    description: '',
                                });
                                setItems([
                                    {
                                        product_id: null,
                                        product_name: '',
                                        description: '',
                                        quantity: 1,
                                    },
                                ]);
                                setErrors({});
                                setSuccessMessage('');
                            }}
                            className="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium"
                        >
                            Reset
                        </button>
                        <button
                            type="submit"
                            disabled={saving}
                            className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
                        >
                            {saving
                                ? 'Saving...'
                                : estimationId
                                  ? 'Update Estimation'
                                  : 'Create Estimation'}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    );
}
