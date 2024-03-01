<script setup>
import { router, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
import { useToast } from "primevue/usetoast";

import Dialog from "primevue/dialog";
import Button from "primevue/button";

const appName = import.meta.env.VITE_APP_NAME;

const page = usePage();
const toast = useToast();

const isAccepting = ref(false);

const acceptTerms = () => {
    isAccepting.value = true;

    window.axios
        .patch("/accept-terms", {
            terms_accepted: true,
        })
        .then((result) => {
            if (result.data.accepted) {
                page.props.auth.user.terms_accepted = true;
            }
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message ?? error.response.data,
                life: 5000,
            });
        })
        .finally(() => {
            isAccepting.value = false;
        });
};
</script>

<template>
    <Dialog
        class="lg:w-1/2 break-words"
        :visible="!$page.props.auth.user.terms_accepted"
        :closable="false"
        :draggable="false"
        modal
        header="Terms of Use / Nutzungsbedingungen"
    >
        <h1 class="text-3xl font-semibold">Einleitung</h1>
        <p>
            Willkommen bei <em class="font-medium">{{ appName }}</em
            >, einem interaktiven Chatbot-Dienst für Studierende der
            <em class="font-medium">FH-Aachen</em>. Durch Ihre Nutzung von
            <em class="font-medium">{{ appName }}</em> erklären Sie sich mit
            diesen Nutzungsbedingungen einverstanden. Bitte lesen Sie sie
            sorgfältig durch.
        </p>

        <br />

        <h1 class="text-3xl font-semibold">
            Datenschutz und Verarbeitung personenbezogener Daten
        </h1>
        <p>
            <span class="font-bold">a. </span> Für die Bereitstellung
            personalisierter Dienste und zur Überwachung unserer Services
            erfasst <em class="font-medium">{{ appName }}</em> bestimmte
            personenbezogene Daten, einschließlich Ihres
            <em class="font-medium">ILIAS-Kürzels</em> und Ihrer
            <em class="font-medium">IP-Adresse</em>.
        </p>
        <br />
        <p>
            <span class="font-bold">b. </span>
            <em class="font-medium">{{ appName }}</em> verpflichtet sich zum
            Schutz Ihrer Daten und zur Einhaltung der
            <em class="font-medium">Datenschutz-Grundverordnung (DSGVO)</em>
            sowie aller relevanten Datenschutzgesetze.
        </p>
        <br />
        <p>
            <span class="font-bold">c. </span> Ihre
            <em class="font-medium">Nachrichten</em> und
            <em class="font-medium">Konversationen</em> können von uns zu
            <em class="font-medium">Überwachungs-</em> und
            <em class="font-medium">Verbesserungszwecken</em> mitgelesen werden.
        </p>

        <br />

        <h1 class="text-3xl font-semibold">Zustimmung</h1>
        <p>
            Mit der Nutzung von
            <em class="font-medium">{{ appName }}</em> stimmen Sie der
            Verarbeitung Ihrer personenbezogenen Daten, einschließlich Ihres
            <em class="font-medium">ILIAS-Kürzels</em> und Ihrer
            <em class="font-medium">IP-Adresse</em>, gemäß diesen
            Nutzungsbedingungen zu. Sie erklären sich ebenfalls damit
            einverstanden, dass Ihre Nachrichten zu
            <em class="font-medium">Überwachungs-</em> und
            <em class="font-medium">Verbesserungszwecken</em> mitgelesen werden.
        </p>

        <br />

        <h1 class="text-3xl font-semibold">
            Änderungen der Nutzungsbedingungen
        </h1>
        <p>
            Die <em class="font-medium">FH-Aachen</em> behält sich das Recht
            vor, diese Nutzungsbedingungen zu ändern. Änderungen treten mit
            ihrer Veröffentlichung in Kraft. Ihre fortgesetzte Nutzung von
            <em class="font-medium">{{ appName }}</em> nach solchen Änderungen
            gilt als Zustimmung zu den aktualisierten Bedingungen.
        </p>

        <br />

        <h1 class="text-3xl font-semibold">Kontakt</h1>
        <p>
            Bei Fragen kontaktieren Sie bitte
            <em class="font-medium underline"
                ><a href="mailto:remmy@fh-aachen.de">remmy@fh-aachen.de</a></em
            >
            oder
            <em class="font-medium underline"
                ><a href="mailto:gani.aytan@alumni.fh-aachen.de"
                    >gani.aytan@alumni.fh-aachen.de</a
                ></em
            >
        </p>

        <br />

        <div class="flex gap-3 justify-center">
            <div>
                <Button
                    @click="router.post('/logout')"
                    label="Ich lehne ab"
                    severity="danger"
                    icon="pi pi-times"
                />
            </div>
            <div>
                <Button
                    @click="acceptTerms"
                    label="Ich stimme zu"
                    :icon="
                        isAccepting ? 'pi pi-spin pi-spinner' : 'pi pi-check'
                    "
                />
            </div>
        </div>
    </Dialog>
</template>
