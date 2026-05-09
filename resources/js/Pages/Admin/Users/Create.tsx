import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import UserForm from '@/Components/Users/UserForm';
import AdminLayout from '@/Layouts/AdminLayout';
import { useTranslation } from 'react-i18next';

type Role = {
    id: number;
    name: string;
};

type Props = {
    roles: Role[];
};

export default function Create({ roles }: Props) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        borrow_limit: 3,
        role: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('admin.users.store'));
    };

    return (
        <AdminLayout>
            <UserForm
                title={t('create_user')}
                description={t('create_user_description')}
                submitLabel={t('save_user')}
                roles={roles}
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                submit={submit}
            />
        </AdminLayout>
    );
}
