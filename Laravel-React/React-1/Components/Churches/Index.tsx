import React, { ChangeEvent, useState } from 'react';
import { Link } from "@inertiajs/inertia-react";
import { Inertia } from "@inertiajs/inertia";
import useRoute from "@/Hooks/useRoute";
import Icons from '../../Icons/Icon';

// Common
import Table from '../../Common/Table/Index';
import LinkUrl from "@/Common/LinkUrl/Index";
import Button from "@/Common/Button/Index";
import SearchFilter from "@/Common/Filters/SearchFilter";
import Select from "@/Common/Select/Index";

// Types
import { IColumn, ITableProps } from "@/types";
import { checkLoggedUserPermission } from "@/Utils/Main";
import useTypedPage from "@/Hooks/useTypedPage";

const ChurchTable = () => {
    const route = useRoute();
    const props = useTypedPage<ITableProps>().props;
    const { data, links } = props.churches;
    const { buttons, church } = props.translations;
    const dioceses = props.dioceses;
    const createUrl = 'churches.create';
    const createName = buttons.add_new_church;
    const canCreateChurch = checkLoggedUserPermission(props.user, 'churches.create');
    const showUrl = 'churches.show';
    const [isShowDestroyModal, setIsShowDestroyModal] = useState(false);
    const [destroyedUserId, setDestroyedUserId] = useState<number | null>(null);

    const destroy = () => {
        setIsShowDestroyModal(false);
        if (destroyedUserId) {
            Inertia.delete(route('churches.destroy', destroyedUserId));
        }
    }

    const openModal = (id: number) => {
        setIsShowDestroyModal(true);
        setDestroyedUserId(id)
    }

    const handleSelectDiocese = (e: ChangeEvent<HTMLSelectElement>) => {
        Inertia.get(route('churches.index', e.target.value && {...route().params, diocese: e.target.value}));
    }

    const columns: IColumn[] = [
        {
            id: 1,
            name: church.table_columns.name,
            width: '20%',
            selector: 'name',
        },
        {
            id: 2,
            name: church.table_columns.diocese,
            width: '20%',
            render: (id) => {
                const churchItem = data.find((church) => church.id === id);

                if (! churchItem) {
                    return null;
                }

                return churchItem.diocese?.name || church.form_fields.the_church_does_not_belong_to_any_diocese;
            },
        },
        {
            id: 3,
            name: church.table_columns.address,
            width: '15%',
            selector: 'address_view',
        },
        {
            id: 4,
            name: church.table_columns.priest,
            width: '40%',
            render: (id) => {
                const churchItem = data.find((church) => church.id === id);

                if (!churchItem) {
                    return null;
                }

                return props.church_users_with_roles[id]?.map((user) => {
                    return (
                        <div key={user.user_id} className="w-full">
                            <span className="bg-transparent cursor-default">
                                {user.user_name} - {user.role_name}
                            </span>
                        </div>
                    );
                })
            },
        },
        {
            id: 5,
            name: church.table_columns.edit,
            width: '5%',
            render: (id) => {
                const canEditChurch = checkLoggedUserPermission(props.user, 'churches.edit', id);
                const canDestroyChurch = checkLoggedUserPermission(props.user, 'churches.destroy', id);
                return (
                    <div className="w-full flex  min-w-[80px]">
                        {canEditChurch &&
                            <Link className="bg-transparent relative group mr-1" href={route('churches.edit', id)} as="button">
                                <div className={"absolute bottom-0 flex flex-col items-center hidden mb-6 ml-2 group-hover:flex"}>
                                    <div className="absolute  bottom-1  flex justify-center items-center hidden group-hover:flex">
                                        <span
                                            className="relative z-10 p-2 text-xs leading-none text-black whitespace-nowrap rounded-lg bg-gray-200">
                                            {buttons.edit}
                                        </span>
                                    </div>
                                </div>
                                <Icons name="Edit" fill='#3298da' />
                            </Link>
                        }
                        {canDestroyChurch &&
                            <button className="bg-transparent relative group ml-1" onClick={() => openModal(id)}>
                                <div className={"absolute bottom-0 flex flex-col items-center hidden mb-6 ml-2 group-hover:flex"}>
                                    <div className="absolute  bottom-1  flex justify-center items-center hidden group-hover:flex">
                                        <span
                                            className="relative z-10 p-2 text-xs leading-none text-black whitespace-nowrap rounded-lg bg-gray-200">
                                             {buttons.delete}
                                        </span>
                                    </div>
                                </div>
                                <Icons name="Delete" fill='red' />
                            </button>
                        }
                    </div>
                );
            },
        },
    ];

    return (
        <>
            <div className="flex flex-col items-center w-[219px] mx-auto sm:w-full md:flex-row sm:justify-between md:items-start">
                <div className="flex items-start flex-col md:flex-row sm:mb-3 md:mb-0">
                    <SearchFilter />
                    {dioceses.length > 1 &&
                        <div className='flex pl-3'>
                            <Select onChange={(e) => handleSelectDiocese(e)}
                                    id="type"
                                    name="type"
                                    className="mt-3 md:mt-0 ml-3 pr-5"
                                    value={props.diocese as string ?? ''}>
                                <option value='all'>{church.form_fields.filter_by_diocese}</option>
                                {
                                    dioceses?.map((diocese) => {
                                        return (
                                            <option key={diocese.id} value={diocese.id}>{diocese.name}</option>
                                        )
                                    })
                                }
                            </Select>
                        </div>
                    }
                </div>
                {canCreateChurch && createName &&
                    <LinkUrl
                        children={createName}
                        url='churches.create'
                        className='focus:outline-none text-white bg-green-700 hover:bg-green-800'
                    />
                }
            </div>
            <Table
                columns={columns}
                data={data}
                links={links}
                createUrl={createUrl}
                canCreateItem={canCreateChurch}
                showUrl={showUrl}
                showSearchFilter={false}
            />

            {isShowDestroyModal &&
                <div className="modal-background w-full h-screen bg-[#00000033] fixed top-0 left-0 z-10 flex items-center justify-center">
                    <div className="w-[440px] bg-white p-[20px] border border-gray-300 relative flex flex-col items-center">
                        <div className="absolute top-3 right-3 cursor-pointer" onClick={() => setIsShowDestroyModal(false)}>
                            <Icons name="ThinClose" />
                        </div>

                        <h2 className="font-bold border-b border-gray-300 text-center my-2 mt-4">{church.delete_confirm_message}</h2>

                        <div className="w-full flex justify-end mt-7">
                            <LinkUrl
                                children={buttons.no as string}
                                url="churches.index"
                                className="text-gray-900"
                            />

                            <Button
                                className='ml-3 mt-3 sm:ml-0 sm:mt-0 md:mr-0'
                                type="button"
                                onClick={() => destroy()}>
                                {buttons.yes}
                            </Button>
                        </div>
                    </div>
                </div>
            }
        </>
    );
}

export default ChurchTable;
