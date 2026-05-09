import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import UserForm from '@/Components/Users/UserForm';
import AdminLayout from '@/Layouts/AdminLayout';
import { useTranslation } from 'react-i18next';

type Role = {
    id: number;
    name: string;
};

type User = {
    id: number;
    name: string;
    email: string;
    borrow_limit: number;
    roles: Array<{
        id: number;
        name: string;
    }>;
};

type Props = {
    user: User;
    roles: Role[];
    isSuperAdminUser: boolean;
};

export default function Edit({ user, roles, isSuperAdminUser }: Props) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm({
        name: user.name ?? '',
        email: user.email ?? '',
        password: '',
        borrow_limit: user.borrow_limit ?? 3,
        role: user.roles[0]?.name ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        put(route('admin.users.update', user.id));
    };

    return (
        <AdminLayout>
            <UserForm
                title={t('edit_user')}
                description={t('edit_user_description')}
                submitLabel={t('update_user')}
                roles={roles}
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                submit={submit}
                isEdit
                isSuperAdminUser={isSuperAdminUser}
            />
        </AdminLayout>
    );
}
