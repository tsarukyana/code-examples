import React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import useTypedPage from "@/Hooks/useTypedPage";

// Types
import { TableProps } from '@/types';

// Components
import ChurchTable from '../../Components/Churches/Index';

const Index = () => {
    const page = useTypedPage<TableProps>().props;
    const churchTranslation = page.translations.church;
    const dashboardTranslation = page.translations.dashboard;

    return (
        <AppLayout
            title={churchTranslation.churches}
            renderHeader={() => (
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    {churchTranslation.churches}
                </h2>
            )}
        >
            <div className="py-12">
                <div className="max-w-full mx-auto px-3 sm:px-6 lg:px-8">
                    <div className="bg-white shadow-sm sm:rounded-lg">
                        <ChurchTable />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

export default Index;
