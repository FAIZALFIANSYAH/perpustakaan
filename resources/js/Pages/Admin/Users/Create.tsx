import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import UserForm from '@/Components/Users/UserForm';
import AdminLayout from '@/Layouts/AdminLayout';

type Role = {
    id: number;
    name: string;
};

type Props = {
    roles: Role[];
};

export default function Create({ roles }: Props) {
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
                title="Create User"
                description="Tambah akun baru dan tentukan role aksesnya."
                submitLabel="Save User"
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
