import React, {
    ChangeEventHandler,
    FormEventHandler,
    useRef,
    useState,
    useEffect,
    FC,
} from 'react';
import useRoute from "@/Hooks/useRoute";
import { useForm } from "@inertiajs/inertia-react";
import { GoogleMap, Marker, useJsApiLoader, LoadScriptProps } from '@react-google-maps/api';
import Cropper from "react-cropper";
import RegularProceduresProvider from "@/Context/RegularProcedures";
import "cropperjs/dist/cropper.css"

// Common
import Input from "@/Common/Input/Index";
import Textarea from "@/Common/Textarea/Index";
import Button from '@/Common/Button/Index';

// Components
import Autocomplete from './Autocomplete/Autocomplete';
import Icons from "@/Icons/Icon";
import SelectUserForChurch from './SelectUserForChurch/Index';
import RegularProcedures from './RegularProcedures/Index';

// SVG
import ChurchMarker from '@/Icons/ChurchMarker.svg';

// Types
import {
    Church,
    ICreateFormState,
    IUserSelect,
    TableProps,
    IUploadedImg,
} from "@/types";
import uniqueIdGenerate from "@/Utils/UniqueIdGenerate";
import useTypedPage from "@/Hooks/useTypedPage";
import LinkUrl from "@/Common/LinkUrl/Index";
import Select from "@/Common/Select/Index";

interface IMarker {
    latitude: number;
    longitude: number;
}

const containerStyle = {
    width: '100%',
    height: '485px',
    border: '1px solid rgb(209, 213, 219)',
};

interface ICreateForm {
    editData?: Church;
}

const libraries: LoadScriptProps["libraries"] = ["places"];

const ChurchForm: FC<ICreateForm> = ({editData}) => {
    const page = useTypedPage<TableProps>();
    const route = useRoute();
    const props = useTypedPage<TableProps>().props;
    const dioceses = props.dioceses;
    const user = props.user;
    const buttonsTranslation = props.translations.buttons;
    const churchTranslation = props.translations.church;
    const labelsTranslation = props.translations.labels;
    const google_api_key = props.google_api_key;
    const [isImgModalOpen, setIsImgModalOpen] = useState(false);
    const cropperRef = useRef<HTMLImageElement>(null);
    const photoPath = useRef<HTMLInputElement>(null);
    const [isActivePhotoDeleteButton, setIsActivePhotoDeleteButton] = useState(false);
    const [isActiveButton, setIsActiveButton] = useState(false);
    const [isActivePlus, setIsActivePlus] = useState(false);
    const [assignNewUserObj, setAssignNewUserObj] = useState({
        id: uniqueIdGenerate(),
        isLastSelect: true,
        full_name: '',
        church_roles: [],
    });
    const [uploadedImg, setUploadedImg] = useState<IUploadedImg>({
        data: editData?.photo_path ?? 'churches/church_default_photo.png',
        name: editData?.name ?? '',
        isBase64: false,
    });
    const [marker, setMarker] = useState<IMarker>({
        latitude: editData?.coordinate.coordinates[1] || 40.16557,
        longitude: editData?.coordinate.coordinates[0] || 44.2946,
    });
    const methods = useForm<ICreateFormState>({
        _method: editData?.id ? 'put' : 'post',
        diocese_id: editData?.diocese_id,
        name: editData?.name || '',
        short_description: editData?.short_description || '',
        photo_path: editData?.photo_path ?? null,
        phone_number: editData?.phone_number ?? '',
        additional_phone_number: editData?.additional_phone_number ?? '',
        selected_users: props.church_users || [],
        history: editData?.history || '',
        type: editData?.type,
        latitude: marker.latitude,
        longitude: marker.longitude,
        address: editData?.address || '',
        address_view: editData?.address_view || '',
        city: editData?.city || '',
        country: editData?.country || '',
        place_id: editData?.place_id || '',
        instagram: editData?.instagram || '',
        website: editData?.website || '',
        facebook: editData?.facebook || '',
        telegram: editData?.telegram || '',
        regular_procedures_by_key: editData?.regular_procedures_by_key || {},
    });
    const { data, setData, post, errors, clearErrors } = methods;

    const handleSubmit: FormEventHandler<HTMLFormElement> = (e) => {
        e.preventDefault();
        const routeUrl = editData?.id ? route('churches.update', editData.id) : route('churches.store');
        if (user?.roles?.some(role => role.name === "Diocese-Administrator") && ! data.diocese_id) {
            // Here we set diocese_id to dioceses[0] because we have only one diocese for this user
            data.diocese_id = dioceses[0]?.id
        }
        post(routeUrl);
    };

    const handleChange: ChangeEventHandler<HTMLInputElement & HTMLTextAreaElement & HTMLSelectElement> = ({target: {name, value}}) => {
        clearErrors(name as keyof ICreateFormState);
        setIsActiveButton(true);
        setData(name as keyof ICreateFormState, value);
    };

    const { isLoaded } = useJsApiLoader({
        id: 'google-map-script-1',
        googleMapsApiKey: google_api_key,
        libraries,
    });

    const getCoordinates = (e: google.maps.MapMouseEvent) => {
        setIsActiveButton(true);
        setData({
            ...data,
            latitude: e.latLng?.lat() || 0,
            longitude: e.latLng?.lng() || 0,
        });
        setMarker({
            latitude: e.latLng?.lat() || 0,
            longitude: e.latLng?.lng() || 0,
        });
    }

    const onCrop = () => {
        const imageElement: any = cropperRef?.current;
        const cropper: any = imageElement?.cropper;

        setUploadedImg({
            ...uploadedImg,
            data: cropper.getCroppedCanvas().toDataURL()
        })
        cropper.getCroppedCanvas().toBlob(function (blob: Blob | null) {
            setData('photo_path', blob);
        }, 'image/jpeg');

        setIsImgModalOpen(false);
        setIsActiveButton(true);
    };

    const handleDeletePhoto = () => {
        setIsActiveButton(true);
        setIsActivePhotoDeleteButton(false);
        if (photoPath && photoPath.current) {
            photoPath.current.value = '';
        }
        setData('photo_path', null);
        setUploadedImg({
            data: 'churches/church_default_photo.png',
            name: '',
            isBase64: false,
        });
    }

    const handlePhotoUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        clearErrors('photo_path');
        const files = e.target.files;

        setIsImgModalOpen(true);
        if (FileReader && files && files.length) {
            const fileReader = new FileReader();
            fileReader.onload = () => {
                setUploadedImg({
                    data: fileReader.result as string,
                    name: files[0].name,
                    isBase64: true,
                });
            }
            fileReader.readAsDataURL(files[0]);
        }
        setIsActivePhotoDeleteButton(true);
    }

    const handleUserSelect = (e: IUserSelect) => {
        let newSelectedUsers = [ ...data.selected_users ];
        let changedAlreadySelectedUser = false;
        newSelectedUsers.forEach((item, index) => {
            if (item.id === e.dataId) {
                changedAlreadySelectedUser = true;
                newSelectedUsers[index] = {
                    ...item,
                    id: typeof e.id === 'string' ? Number(e.id) : e.id,
                    full_name: e.label ?? '',
                };
            }
        });
        if (! changedAlreadySelectedUser) {
            newSelectedUsers.push({
                id: typeof e.id === 'string' ? Number(e.id) : e.id,
                full_name: e.label ?? '',
                church_roles: [],
            });
            setAssignNewUserObj( {
                id: uniqueIdGenerate(),
                isLastSelect: true,
                full_name: '',
                church_roles: [],
            });
        }
        setData('selected_users', newSelectedUsers);
        setIsActiveButton(true);
    }

    const handleUnassignedUser = (e: React.MouseEvent<HTMLSpanElement>) => {
        if (e?.currentTarget?.dataset?.id) {
            setData('selected_users', [ ...data.selected_users ].filter(item => item.id !== Number(e.currentTarget.dataset.id ?? '0')));
            setIsActiveButton(true);
        }
    }

    const handleRoleSelect: ChangeEventHandler<HTMLInputElement> = (e) => {
        let newSelectedUsers = [ ...data.selected_users ];
        let selectedIndex: number|null = null;
        newSelectedUsers.forEach((item, index) => {
            if (item.id === Number(e.target.dataset.id)) {
                newSelectedUsers[index] = {
                    ...item,
                    church_roles: [ e.target.value ],
                };
                selectedIndex = index;
            }
        });
        setData('selected_users', newSelectedUsers);
        setIsActiveButton(true);
        if (e.target.dataset.islastselect === 'true') {
            setIsActivePlus(true);
        }

        if (selectedIndex !== null) {
            clearErrors(`selected_users.${selectedIndex}` as keyof ICreateFormState);
        }
    }

    const handleAddSelect = () => {
        setIsActivePlus(false);
    }

    const selectedUserIds = data.selected_users.map(item => item.id) ?? [];
    const isSuperAdmin = user?.roles?.some(role => role.name === "Super-Admin");
    const hasMoreThanOneDioceseAdminDiocese = user?.roles?.some(role => role.name === "Diocese-Administrator") && dioceses.length > 1;

    useEffect(() => {
        clearErrors();
        if (editData) {
            setData({
                _method: editData.id ? 'put' : 'post',
                diocese_id: editData.diocese_id,
                name: editData.name || '',
                short_description: editData.short_description || '',
                photo_path: editData.photo_path ?? null,
                phone_number: editData.phone_number ?? '',
                additional_phone_number: editData.additional_phone_number ?? '',
                selected_users: props.church_users || [],
                history: editData.history || '',
                type: editData.type,
                latitude: marker.latitude,
                longitude: marker.longitude,
                address: editData.address || '',
                address_view: editData.address_view || '',
                city: editData.city || '',
                country: editData.country || '',
                place_id: editData.place_id || '',
                instagram: editData.instagram || '',
                website: editData.website || '',
                facebook: editData.facebook || '',
                telegram: editData.telegram || '',
                regular_procedures_by_key: editData?.regular_procedures_by_key || {}
            });
        }
    }, [page.props.locale]);

    return (
        <div className="w-full">
            <form className="church-form bg-white shadow-md rounded px-3 sm:px-8 pt-6 pb-8" onSubmit={handleSubmit} encType="multipart/form-data">
                {isImgModalOpen && uploadedImg.data &&
                    <div className="w-full h-screen fixed top-0 left-0 bg-[#00000033] z-10 flex items-center justify-center flex-col">
                        <Cropper
                            src={uploadedImg.data}
                            style={{ height: 400, width: "100%" }}
                            initialAspectRatio={1}
                            aspectRatio={1}
                            guides={false}
                            ref={cropperRef}
                        />
                        <Button className="mt-3" onClick={onCrop}>{labelsTranslation.crop}</Button>
                    </div>
                }
                <div className="flex flex-col sm:flex-row justify-between">
                    <div className="w-full sm:w-1/2">
                        {(isSuperAdmin || hasMoreThanOneDioceseAdminDiocese) &&
                            <div className="mb-5">
                                <span className="block mb-2 text-sm text-gray-900 font-bold">{churchTranslation.form_fields.diocese}</span>
                                <Select onChange={handleChange}
                                        id="diocese_id"
                                        name="diocese_id"
                                        value={data.diocese_id ?? ''}>
                                    {isSuperAdmin &&
                                        <option key={`diocese-0`} value="">{churchTranslation.form_fields.the_church_does_not_belong_to_any_diocese}</option>
                                    }
                                    {
                                        dioceses.map((diocese) => {
                                            return (
                                                <option key={`diocese-${diocese.id}`} value={diocese.id}>{diocese.name}</option>
                                            )
                                        })
                                    }
                                </Select>
                                {errors.diocese_id &&
                                    <p className="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                                        {errors.diocese_id}
                                    </p>
                                }
                            </div>
                        }
                        <div className="mb-6">
                            <span className="block mb-2 text-sm text-gray-900 font-bold">{churchTranslation.form_fields.name_of_the_church}*</span>
                            <Input
                                type="text"
                                placeholder={churchTranslation.form_fields.name}
                                value={data.name}
                                name="name"
                                errorMessage={errors.name}
                                onChange={handleChange}
                            />
                        </div>
                        <div className="mb-5">
                            <span className="block mb-2 text-sm text-gray-900 font-bold">{churchTranslation.form_fields.status}</span>
                            <Select onChange={handleChange}
                                    id="type"
                                    name="type"
                                    value={data.type}>
                                <option value={'active'}>{churchTranslation.form_fields.active}</option>
                                <option value={'inactive'}>{churchTranslation.form_fields.inactive}</option>
                                <option value={'occupied'}>{churchTranslation.form_fields.occupied}</option>
                                <option value={'half-built'}>{churchTranslation.form_fields.half_built}</option>
                            </Select>
                            {errors.type &&
                                <p className="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                                    {errors.type}
                                </p>
                            }
                        </div>
                        <div className="mb-5 flex justify-between items-start">
                            <div className="w-3/4 overflow-hidden flex items-center">
                                <label className="flex flex-col cursor-pointer items-center" htmlFor="img">
                                    <span className='block mb-2 text-sm text-gray-900 font-bold'>{buttonsTranslation.upload_photo}</span>
                                    <div className="max-w-max mt-1">
                                        <Icons name="UploadFile" />
                                    </div>
                                </label>
                                <input
                                    type="file"
                                    name="photo_path"
                                    id="img"
                                    className="hidden"
                                    onChange={handlePhotoUpload}
                                    ref={photoPath}
                                />
                                {errors.photo_path &&
                                    <p className="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                                        {errors.photo_path}
                                    </p>
                                }
                            </div>
                            <div className='flex flex-col items-end'>
                                {(isActivePhotoDeleteButton || !uploadedImg.data?.includes('church_default_photo')) &&
                                    <Icons onClick={handleDeletePhoto} name="Close" className='w-[15px] h-[15px] mb-1 cursor-pointer' />
                                }
                                <img
                                    className={`w-[100px] ${!uploadedImg.data ? '' : 'border border-gray-300 rounded'}`}
                                    src={(!editData?.id
                                        ? uploadedImg.isBase64 ? uploadedImg.data : '/storage/churches/church_default_photo.png'
                                        : editData?.id && !uploadedImg.isBase64 ? `/storage/${uploadedImg.data}` : uploadedImg.data) as string}
                                    alt={churchTranslation.church_photo}
                                />
                            </div>
                        </div>
                        { !!data.selected_users?.length &&
                            <>
                                <span className="block mb-2 text-sm text-gray-900 font-bold">{churchTranslation.form_fields.choose_a_priest}</span>
                                {
                                    data.selected_users.map((item, index) => {
                                        return (
                                            <SelectUserForChurch
                                                key={item.id}
                                                errors={errors}
                                                users={props.users}
                                                handleUnassignedUser={handleUnassignedUser}
                                                userId={+item.id}
                                                handleUserSelect={handleUserSelect}
                                                handleRoleSelect={handleRoleSelect}
                                                item={item}
                                                selectedIndex={index}
                                                isLastSelect={item.isLastSelect}
                                                selectedUserIds={selectedUserIds}
                                            />
                                        )
                                    })
                                }
                            </>
                        }
                        <span className="block mb-2 text-sm text-gray-900 font-bold">{churchTranslation.form_fields.choose_a_priest}</span>
                        { !!assignNewUserObj &&
                            <SelectUserForChurch
                                key={assignNewUserObj.id}
                                errors={errors}
                                users={props.users}
                                handleUnassignedUser={handleUnassignedUser}
                                userId={assignNewUserObj.id}
                                item={assignNewUserObj}
                                isLastSelect={assignNewUserObj.isLastSelect}
                                handleUserSelect={handleUserSelect}
                                handleRoleSelect={handleRoleSelect}
                                selectedUserIds={selectedUserIds}
                            />
                        }
                        { isActivePlus &&
                            <span
                                className="w-[40px] h-[40px] text-3xl cursor-pointer border-2 border-black rounded-full flex justify-center items-center mb-5"
                                onClick={handleAddSelect}
                            >+</span>
                        }
                        <div className="mb-4 p-3 border border-[#CECECE]">
                            <span className="mb-2 block text-md font-bold">{churchTranslation.regular_procedures.title}</span>

                            <RegularProceduresProvider formData={methods} setIsActiveButton={setIsActiveButton} editDataId={editData?.id}>
                                <RegularProcedures />
                            </RegularProceduresProvider>

                        </div>
                        <div className="mb-4">
                            <span className="mb-2 block text-sm font-bold">{churchTranslation.form_fields.additional_information}</span>
                            <Textarea
                                placeholder={churchTranslation.form_fields.description}
                                name="short_description"
                                errorMessage={errors.short_description}
                                onChange={handleChange}
                                value={data.short_description}
                            />
                        </div>
                        <div className="mb-4">
                            <span className="mb-2 block text-sm font-bold">{churchTranslation.form_fields.church_responsible_phone_number}</span>
                            <Input
                                type="text"
                                placeholder={churchTranslation.form_fields.church_phone_number}
                                name="phone_number"
                                errorMessage={errors.phone_number}
                                onChange={handleChange}
                                value={data.phone_number}
                            />
                        </div>
                        <div className="mb-4">
                            <span className="mb-2 block text-sm font-bold">{churchTranslation.form_fields.church_responsible_additional_phone_number}</span>
                            <Input
                                type="text"
                                placeholder={churchTranslation.form_fields.church_additional_phone_number}
                                name="additional_phone_number"
                                errorMessage={errors.additional_phone_number}
                                onChange={handleChange}
                                value={data.additional_phone_number}
                            />
                        </div>
                        <span className="mb-2 block text-sm font-bold">{churchTranslation.social_links.links}</span>
                        <div className="mb-1">
                            <Input
                                type="text"
                                placeholder={churchTranslation.social_links.facebook}
                                name="facebook"
                                errorMessage={errors.facebook}
                                onChange={handleChange}
                                value={data.facebook || ''}
                            />
                        </div>
                        <div className="mb-1">
                            <Input
                                type="text"
                                placeholder={churchTranslation.social_links.website}
                                name="website"
                                errorMessage={errors.website}
                                onChange={handleChange}
                                value={data.website || ''}
                            />
                        </div>
                        <div className="mb-1">
                            <Input
                                type="text"
                                placeholder={churchTranslation.social_links.instagram}
                                name="instagram"
                                errorMessage={errors.instagram}
                                onChange={handleChange}
                                value={data.instagram || ''}
                            />
                        </div>
                        <div className="mb-4">
                            <Input
                                type="text"
                                placeholder={churchTranslation.social_links.telegram}
                                name="telegram"
                                errorMessage={errors.telegram}
                                onChange={handleChange}
                                value={data.telegram || ''}
                            />
                        </div>
                        <div>
                            <span className="mb-2 block text-sm font-bold">{churchTranslation.form_fields.church_history}*</span>
                            <Textarea
                                placeholder={churchTranslation.form_fields.history}
                                name="history"
                                className="h-[250px]"
                                errorMessage={errors.history}
                                onChange={handleChange}
                                value={data.history}
                            />
                        </div>
                    </div>
                    <div className="w-full sm:w-1/2 sm:ml-10">
                        <div className="mb-6">
                            <span className="block mb-2 text-sm text-gray-900 font-bold">{churchTranslation.form_fields.church_address}*</span>
                            <Input
                                type="text"
                                placeholder={churchTranslation.form_fields.city_street}
                                value={data.address_view}
                                name="address_view"
                                errorMessage={errors.address_view}
                                onChange={handleChange}
                            />
                        </div>
                        {isLoaded && (
                        <>
                        <div>
                            <Autocomplete
                                formData={data}
                                setData={setData}
                                setMarker={setMarker}
                                inputPlaceholder={churchTranslation.form_fields.search}
                                errorMessage={errors.address}
                                isLoaded={isLoaded}
                                editDataId={editData?.id}
                                inputName='address'
                                labelName={churchTranslation.form_fields.select_address_by_map +'*'}
                                setIsActiveButton={setIsActiveButton}
                                clearErrors={clearErrors}
                            />
                        </div>
                            <GoogleMap
                                mapContainerStyle={containerStyle}
                                center={{ lat: marker.latitude, lng: marker.longitude }}
                                zoom={15}
                                onClick={getCoordinates}
                                options={{ streetViewControl: false }}
                            >
                                <Marker
                                    position={{ lat: marker.latitude, lng: marker.longitude }}
                                    draggable
                                    icon={{
                                        url: ChurchMarker,
                                    }}
                                    onDragEnd={getCoordinates}
                                />
                            </GoogleMap>
                        </>
                        )}
                    </div>
                </div>
                <div className="mt-3 flex items-center justify-end">
                    <LinkUrl
                        children={buttonsTranslation.cancel as string}
                        url="churches.index"
                        className="text-gray-900"
                    />
                    <Button
                        type={isActiveButton ? "submit" : "button"}
                        className={isActiveButton ? "bg-green-800" : "bg-[#A5E1AD] border-slate-500 cursor-default"}>
                        {buttonsTranslation.submit}
                    </Button>
                </div>
            </form>
        </div>
    );
};

export default React.memo(ChurchForm);
