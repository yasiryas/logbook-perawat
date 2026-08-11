<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    CalendarCheck,
    ClipboardList,
    LayoutGrid,
    ListChecks,
    Medal,
    NotebookTabs,
    UserRound,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import TeamSwitcher from '@/components/TeamSwitcher.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import assessments from '@/routes/assessments';
import { dashboard } from '@/routes';
import indicators from '@/routes/indicators';
import logbook from '@/routes/logbook';
import nurses from '@/routes/nurses';
import recaps from '@/routes/recaps';
import units from '@/routes/units';
import type { NavItem } from '@/types';

const page = usePage();

const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);

const role = computed(
    () => (page.props.auth.user.role as string | undefined) ?? 'perawat',
);

const isManager = computed(() => role.value !== 'perawat');

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboardUrl.value,
        icon: LayoutGrid,
    },
    {
        title: 'Input Logbook Harian',
        href: logbook.edit().url,
        icon: NotebookTabs,
    },
    {
        title: 'Panduan Pengisian',
        href: '/panduan',
        icon: BookOpen,
    },
]);

const recapNavItems = computed<NavItem[]>(() => [
    {
        title: 'Rekap Bulanan',
        href: recaps.monthly().url,
        icon: CalendarCheck,
    },
    {
        title: 'Rekap Tahunan',
        href: recaps.annual().url,
        icon: ClipboardList,
    },
]);

const masterNavItems = computed<NavItem[]>(() => [
    {
        title: 'Unit',
        href: units.index().url,
        icon: ListChecks,
    },
    {
        title: 'Perawat',
        href: nurses.index().url,
        icon: UserRound,
    },
    {
        title: 'Indikator',
        href: indicators.index().url,
        icon: Medal,
    },
]);

const assessNavItems = computed<NavItem[]>(() => [
    {
        title: 'Penilaian Tahunan',
        href: assessments.index().url,
        icon: CalendarCheck,
    },
]);

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardUrl">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <SidebarMenu>
                <SidebarMenuItem>
                    <TeamSwitcher />
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain title="Rekap" :items="recapNavItems" />
            <NavMain
                v-if="isManager"
                title="Master Data"
                :items="masterNavItems"
            />
            <NavMain
                v-if="isManager"
                title="Penilaian"
                :items="assessNavItems"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
