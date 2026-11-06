<script setup>
import { computed } from 'vue'
import { fmtDate } from '@/utils/format'
import Panel from '@/components/ui/panels/Display.vue'
import Button from '@/components/ui/form/Button.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'

// "Verlängerung" sidebar panel. Nothing is sent automatically (Kundenvorgabe):
// when an applicant gets in touch by email or phone, the office sends them the
// renewal link from here. With it they confirm or update their data (→ status
// Verlängert, 6 more months — also for archived applications) or delete the
// application themselves. A renewal uses the link up. Show.vue owns the
// flow (confirm dialog + API + toast) and hands it in via onSend.
const props = defineProps({
	application: { type: Object, required: true },
	sending: { type: Boolean, default: false },
	onSend: { type: Function, required: true },
})

const email = computed(() => props.application.main_applicant?.email || null)
const canSend = computed(() => props.application.is_renewable && !!email.value)
const isActive = computed(() => ['opened', 'extended'].includes(props.application.status.value))
const isArchived = computed(() => props.application.status.value === 'archived')
// Without rows the list would render only its top and bottom border (a double
// line); the text then takes the single divider under the title instead.
const hasRows = computed(() => isActive.value || !!props.application.renewal_link_sent_at)
const textClass = computed(() => hasRows.value ? 'mt-15' : 'border-t border-black/20 pt-15')
</script>

<template>
	<Panel title="Verlängerung">
		<InfoList v-if="hasRows">
			<InfoRow v-if="isActive" label="Gültig bis">
				{{ fmtDate(application.valid_until) }}
			</InfoRow>
			<InfoRow v-if="application.renewal_link_sent_at" label="Link gesendet am">
				{{ fmtDate(application.renewal_link_sent_at) }}
			</InfoRow>
		</InfoList>

		<p v-if="canSend" class="text-sm text-black" :class="textClass">
			Meldet sich die Person, erhält sie per E-Mail einen Link, um ihre Angaben
			zu prüfen und die Bewerbung zu verlängern<template v-if="isArchived">. Die
			archivierte Bewerbung wird damit wieder aktiv</template>.
		</p>
		<p v-else-if="!application.is_renewable" class="text-sm text-gray" :class="textClass">
			Bewerbungen mit Status KNIF können nicht per Link verlängert werden.
		</p>
		<p v-else class="text-sm text-gray" :class="textClass">
			Die Hauptperson hat keine E-Mail-Adresse.
		</p>

		<div v-if="canSend" class="mt-20">
			<Button variant="primary" size="sm" icon="paper-plane-tilt" :loading="sending" @click="onSend">
				{{ application.renewal_link_sent_at ? 'Link erneut senden' : 'Verlängerungslink senden' }}
			</Button>
		</div>
	</Panel>
</template>
