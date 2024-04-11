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
                page.props.auth.user.terms_accepted_at = new Date();
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
        class="scroll-container w-[90%] break-words lg:w-1/2"
        :visible="!$page.props.auth.user.terms_accepted_at"
        :closable="false"
        :draggable="false"
        modal
        header="Terms of Use / Nutzungsbedingungen"
    >
        <small class="italic">Version: 11.04.2024</small>

        <h1 class="text-2xl font-semibold">Einleitung</h1>
        <p>
            Willkommen bei <em class="font-medium">{{ appName }}</em
            >, einem interaktiven Chatbot-Dienst für Studierende der
            <em class="font-medium">FH-Aachen</em>. Durch Ihre Nutzung von
            <em class="font-medium">{{ appName }}</em> erklären Sie sich mit
            diesen Nutzungsbedingungen einverstanden. Bitte lesen Sie sie
            sorgfältig durch.
        </p>

        <br />

        <p>
            Bitte beachten Sie, dass die Informationen und Antworten, die Sie
            durch
            <em class="font-medium">{{ appName }}</em> erhalten, lediglich als
            Hilfestellung dienen. Es liegt in Ihrer Verantwortung, die Antworten
            immer zu überprüfen und zu validieren. Die Inhalte in den
            offiziellen Vorlesungsunterlagen und die dort angegebenen
            Informationen haben Vorrang vor den durch
            <em class="font-medium">{{ appName }}</em>
            bereitgestellten Antworten. Wir bemühen uns um Aktualität und
            Korrektheit der Informationen, können jedoch keine Garantie für die
            Vollständigkeit oder Richtigkeit der bereitgestellten Daten
            übernehmen.
        </p>

        <br />

        <h1 class="text-2xl font-semibold">
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

        <h1 class="text-2xl font-semibold">Missbräuchliche Nutzung</h1>
        <p>
            <span class="font-bold">a. </span>Missbräuchliche Nutzung, die zur
            Beeinträchtigung unserer Systeme, zur Beeinträchtigung der
            Nutzererfahrung oder zur Verletzung unserer Richtlinien führt, ist
            streng untersagt. Dies umfasst das Ausnutzen von Sicherheitslücken,
            unautorisierte Angriffe auf unsere Softwareinfrastruktur, das
            Umgehen von Zugriffsbeschränkungen oder Sicherheitsmaßnahmen, den
            Versuch, Dienste zu überlasten oder zu stören, sowie die Eingabe von
            missbräuchlichen oder schädlichen Prompts an den Chatbot.
        </p>
        <br />
        <p>
            <span class="font-bold">b. </span>Missbräuchliche Prompts
            beinhalten, sind aber nicht beschränkt auf, die Aufforderung zur
            Verbreitung von Hassrede, Gewalt, rechtswidrigen Inhalten oder das
            Manipulieren des Chatbots zu unethischen Zwecken. Wir überwachen die
            Interaktionen mit <em class="font-medium">{{ appName }}</em
            >, um solches Verhalten zu identifizieren und zu verhindern.
        </p>
        <br />
        <p>
            <span class="font-bold">c. </span>Bei Feststellung solcher
            Aktivitäten behalten wir uns das Recht vor, entsprechende Maßnahmen
            zu ergreifen. Diese können von einer Verwarnung bis hin zur
            dauerhaften Sperrung des Zugangs reichen, abhängig von der Schwere
            des Verstoßes.
        </p>
        <br />
        <p>
            <span class="font-bold">d. </span>Es ist ausschließlich gestattet,
            Inhalte zu Themen zu stellen, die sich auf die in den Vorlesungen
            behandelten Inhalte beziehen. Jegliche Anfragen oder Prompts, die
            sich außerhalb dieses Bildungskontextes bewegen, können als
            Missbrauch angesehen werden und unterliegen den gleichen Maßnahmen
            bei einem Verstoß, wie unter den Punkten
            <span class="font-bold">a </span> bis
            <span class="font-bold">c </span> beschrieben.
        </p>

        <br />

        <h1 class="text-2xl font-semibold">Zustimmung</h1>
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
        <p>
            Durch Ihre Zustimmung erkennen Sie außerdem an, dass die Nutzung des
            Dienstes im Einklang mit unseren Richtlinien erfolgen muss. Dazu
            gehört, dass keine missbräuchlichen, rechtswidrigen oder schädlichen
            Inhalte übermittelt werden. Wir behalten uns das Recht vor,
            Maßnahmen gegen Nutzer:innen zu ergreifen, die gegen diese
            Richtlinien verstoßen.
        </p>

        <br />

        <h1 class="text-2xl font-semibold">
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

        <h1 class="text-2xl font-semibold">Kontakt</h1>
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

        <div class="flex flex-wrap justify-center gap-3">
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
