import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

import commonId from './lang/id/global/common.json';
import commonEn from './lang/en/global/common.json';

import navigationId from './lang/id/global/navigation.json';
import navigationEn from './lang/en/global/navigation.json';

import memberId from './lang/id/member/member.json';
import memberEn from './lang/en/member/member.json';

import borrowingId from './lang/id/global/borrowing.json';
import borrowingEn from './lang/en/global/borrowing.json';

import catalogId from './lang/id/global/catalog.json';
import catalogEn from './lang/en/global/catalog.json';

import reservationId from './lang/id/global/reservation.json';
import reservationEn from './lang/en/global/reservation.json';

import finesId from './lang/id/member/fines.json';
import finesEn from './lang/en/member/fines.json';

import librarianId from './lang/id/librarian/librarian.json';
import librarianEn from './lang/en/librarian/librarian.json';

import overdueId from './lang/id/global/overdue.json';
import overdueEn from './lang/en/global/overdue.json';

import paymentVerificationId from './lang/id/member/payment_verification.json';
import paymentVerificationEn from './lang/en/member/payment_verification.json';

import booksId from './lang/id/global/books.json';
import booksEn from './lang/en/global/books.json';

import categoriesId from './lang/id/global/categories.json';
import categoriesEn from './lang/en/global/categories.json';

import librarianMembersId from './lang/id/librarian/members.json';
import librarianMembersEn from './lang/en/librarian/members.json';

import reportsId from './lang/id/librarian/reports.json';
import reportsEn from './lang/en/librarian/reports.json';

import authId from './lang/id/auth.json';
import authEn from './lang/en/auth.json';

import superadminId from './lang/id/superadmin/superadmin.json';
import superadminEn from './lang/en/superadmin/superadmin.json';

import adminReportsId from './lang/id/superadmin/reports.json';
import adminReportsEn from './lang/en/superadmin/reports.json';

import superadminReportsId from './lang/id/superadmin/reports.json';
import superadminReportsEn from './lang/en/superadmin/reports.json';

import superadminFineConfigId from './lang/id/superadmin/fine_config.json';
import superadminFineConfigEn from './lang/en/superadmin/fine_config.json';

import superadminFinesId from './lang/id/superadmin/fines.json';
import superadminFinesEn from './lang/en/superadmin/fines.json';

import superadminOverdueFinesId from './lang/id/superadmin/overdue_fines.json';
import superadminOverdueFinesEn from './lang/en/superadmin/overdue_fines.json';

import superadminPenaltyConfigId from './lang/id/superadmin/penalty_config.json';
import superadminPenaltyConfigEn from './lang/en/superadmin/penalty_config.json';

import superadminUsersId from './lang/id/superadmin/users.json';
import superadminUsersEn from './lang/en/superadmin/users.json';

i18n.use(initReactI18next).init({
    resources: {
        id: {
            translation: {
                ...commonId,
                ...navigationId,
                ...authId,
                ...superadminId,
                ...booksId,
                ...memberId,
                ...borrowingId,
                ...catalogId,
                ...categoriesId,
                ...reservationId,
                ...finesId,
                ...librarianId,
                ...librarianMembersId,
                ...reportsId,
                ...overdueId,
                ...paymentVerificationId,
                ...adminReportsId,
                ...superadminReportsId,
                ...superadminFineConfigId,
                ...superadminFinesId,
                ...superadminOverdueFinesId,
                ...superadminPenaltyConfigId,
                ...superadminUsersId,

            },
        },

        en: {
            translation: {
                ...commonEn,
                ...navigationEn,
                ...authEn,
                ...superadminEn,
                ...booksEn,
                ...memberEn,
                ...borrowingEn,
                ...catalogEn,
                ...categoriesEn,
                ...reservationEn,
                ...finesEn,
                ...librarianEn,
                ...librarianMembersEn,
                ...reportsEn,
                ...overdueEn,
                ...paymentVerificationEn,
                ...adminReportsEn,
                ...superadminReportsEn,
                ...superadminFineConfigEn,
                ...superadminFinesEn,
                ...superadminOverdueFinesEn,
                ...superadminPenaltyConfigEn,
                ...superadminUsersEn,
            },
        },
    },

    lng: localStorage.getItem('lang') || 'id',

    fallbackLng: 'id',

    interpolation: {
        escapeValue: false,
    },
});

export default i18n;
