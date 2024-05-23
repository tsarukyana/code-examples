import React from 'react';

// Components
import AppLayout from '@/Layouts/AppLayout';
import ChurchForm from '@/Components/Churches/ChurchForm';

import { Church, TableProps } from "@/types";
import useTypedPage from "@/Hooks/useTypedPage";

const Edit = () => {
    const page = useTypedPage<TableProps>().props;
    const churchTranslation = page.translations.church;
    const dashboardTranslation = page.translations.dashboard;
    const { church } = page;

    return (
        <AppLayout
            title={dashboardTranslation.dashboard}
            renderHeader={() => (
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    {churchTranslation.churches}
                </h2>
            )}
        >
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <ChurchForm editData={church as Church} />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

export default Edit;
