import React from 'react';
import { createRoot } from 'react-dom/client';
import EstimationForm from './components/estimations/EstimationForm';

// Mount the EstimationForm component if the container exists
const estimationFormContainer = document.getElementById('estimation-form-app');
if (estimationFormContainer) {
    const root = createRoot(estimationFormContainer);
    const estimationId = estimationFormContainer.dataset.estimationId || null;
    root.render(<EstimationForm estimationId={estimationId} />);
}
