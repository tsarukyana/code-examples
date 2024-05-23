import React, { FC } from 'react';
import { Link } from "@inertiajs/inertia-react";
import useRoute from "@/Hooks/useRoute";

// Types
import { Church, TableProps } from "@/types";

// Icons
import Icons from "@/Icons/Icon";

// Img
import defaultChurchImg from '../../../media/img/defaultChurchImg.webp'
import useTypedPage from "@/Hooks/useTypedPage";

interface IDetailsProps {
    church: Church;
}

const Details: FC<IDetailsProps> = ({ church }) => {
    const page = useTypedPage<TableProps>().props
    const route = useRoute();
    const churchTranslation = page.translations.church;

    return (<>
        <div className="flex relative">
            <div className="w-[300px]">
                {
                    church.photo_path ?
                        <img className="w-full block" src={`/storage/${church.photo_path}`} alt={church.name} /> :
                        <img className="w-full block" src={defaultChurchImg} alt={church.name} />
                }
            </div>
            <div className="ml-[80px] mt-3">
                <ul>
                    <li className="text-xl border-b-2 border-gray-400 mb-3">
                        {church.name} - {churchTranslation.form_fields[church.type ? 'active' : 'inactive']}
                    </li>
                    {church.address &&
                        <li>{churchTranslation.form_fields.address} - {church.address}</li>
                    }

                    {page.church_users_with_roles[church.id] ?
                        <>
                            <li className="text-xl border-b-2 border-gray-400 mb-3 mt-5">
                                {churchTranslation.users}
                            </li>
                            <li>{page.church_users_with_roles[church.id].map((user) => {
                                return (
                                    <div key={user.user_id} className="w-full">
                                        {user.user_name} - {user.role_name}
                                    </div>
                                );
                            })}</li>
                        </> : null
                    }
                </ul>
            </div>
            <div className="absolute top-3 right-5">
                <Link className="bg-transparent" href={route('churches.edit', church.id)} as="button">
                    <Icons name="Edit" />
                </Link>
            </div>
        </div>
        <div>
            <div className="mt-10 ml-5 mr-5">
                <p className="indent-7">{church.short_description}</p>
            </div>
            <div className="m-5">
                <p className="indent-7">{church.history}</p>
            </div>
        </div>
    </>)
}

export default Details;
