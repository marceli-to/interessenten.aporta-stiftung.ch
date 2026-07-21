<script setup>
import { useLookupsStore } from '@/stores/lookups'
import { yesNoOptions } from '@/utils/options'
import InfoList from '@/components/ui/info/List.vue'
import EditRow from '@/components/ui/info/EditRow.vue'
import Input from '@/components/ui/form/Input.vue'
import Select from '@/components/ui/form/Select.vue'
import Textarea from '@/components/ui/form/Textarea.vue'

// Current-housing form fields, edited in place on `draft.current_housing`. The
// block is required by the backend and therefore always present on an existing
// applicant; it's scaffolded here for a brand-new co-applicant.
const props = defineProps({
	draft: { type: Object, required: true },
	errors: { type: Object, default: () => ({}) },
})

if (!props.draft.current_housing) {
	props.draft.current_housing = {
		tenant_role: null,
		terminated_by_landlord: false,
		termination_reason: null,
		landlord_name: '',
		landlord_contact_person: null,
		landlord_phone: null,
	}
}

const lookups = useLookupsStore()
</script>

<template>
	<InfoList>
		<EditRow label="Rolle" :error="errors.tenant_role">
			<Select v-model="draft.current_housing.tenant_role" :options="lookups.options('tenant_roles')" :hasError="!!errors.tenant_role" />
		</EditRow>
		<EditRow label="Gekündigt durch Vermieter">
			<Select v-model="draft.current_housing.terminated_by_landlord" :options="yesNoOptions" />
		</EditRow>
		<EditRow v-if="draft.current_housing.terminated_by_landlord" label="Kündigungsgrund" :error="errors.termination_reason">
			<Textarea v-model="draft.current_housing.termination_reason" :hasError="!!errors.termination_reason" />
		</EditRow>
		<EditRow label="Vermieter" :error="errors.landlord_name">
			<Input v-model="draft.current_housing.landlord_name" :hasError="!!errors.landlord_name" />
		</EditRow>
		<EditRow label="Kontaktperson" :error="errors.landlord_contact_person">
			<Input v-model="draft.current_housing.landlord_contact_person" :hasError="!!errors.landlord_contact_person" />
		</EditRow>
		<EditRow label="Telefon Vermieter" :error="errors.landlord_phone">
			<Input v-model="draft.current_housing.landlord_phone" :hasError="!!errors.landlord_phone" />
		</EditRow>
	</InfoList>
</template>
