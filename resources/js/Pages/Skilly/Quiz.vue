<script setup>
import { Head, router, usePage } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Main from "@/Layouts/Main.vue";

import Dropdown from "primevue/dropdown";
import Button from "primevue/button";

const toast = useToast();
const page = usePage();

const settings = ref({
    difficulty: null,
    topic: null,
    count: null,
});

const isCreatingQuiz = ref(false);
const quizData = ref(null);

function shuffle(array) {
    let currentIndex = array.length;

    // While there remain elements to shuffle...
    while (currentIndex !== 0) {
        // Pick a remaining element...
        let randomIndex = Math.floor(Math.random() * currentIndex);
        currentIndex--;

        // And swap it with the current element.
        [array[currentIndex], array[randomIndex]] = [
            array[randomIndex],
            array[currentIndex],
        ];
    }
}

const generate = () => {
    if (
        !settings.value.topic ||
        !settings.value.difficulty ||
        isCreatingQuiz.value
    ) {
        return;
    }

    isCreatingQuiz.value = true;

    window.axios
        .post("/skilly/quiz/create", {
            ...settings.value,
        })
        .then((response) => {
            quizData.value = response.data;

            quizData.value.map((item) => {
                item.answers = [
                    { correct: true, answer: item.correct_answer },
                    { correct: false, answer: item.wrong_answer_a },
                    { correct: false, answer: item.wrong_answer_b },
                    { correct: false, answer: item.wrong_answer_c },
                ];

                item.answered = false;

                shuffle(item.answers);
            });
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message ?? error.response.data,
                life: 5000,
            });

            isCreatingQuiz.value = false;
        })
        .finally(() => {
            //isCreatingQuiz.value = false;
        });
};

const submitAnswer = (questionId) => {
    const question = quizData.value.find((item) => item.id === questionId);
    question.answered = true;
};

const generateAnotherQuiz = () => {
    router.get("/skilly/quiz");
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Home" />

        <Main ref="mainComponent">
            <div v-if="!quizData">
                <h1
                    class="q-animate-gradient bg-gradient-to-r from-emerald-500 via-pink-400 to-blue-500 bg-clip-text p-3 text-center text-8xl font-bold text-transparent max-md:text-4xl"
                >
                    {{
                        isCreatingQuiz ? "Generating Quiz..." : "Skilly AI Quiz"
                    }}
                    <div v-if="isCreatingQuiz" class="mt-4 text-lg">
                        (This may take a while)
                    </div>
                </h1>

                <form
                    v-if="!isCreatingQuiz"
                    class="mx-auto mt-14 flex w-[85%] flex-col gap-4"
                >
                    <div class="grid grid-cols-2 gap-x-8 gap-y-6">
                        <div class="flex flex-col">
                            <Dropdown
                                v-model="settings.topic"
                                :options="page.props.topics"
                                placeholder="Topic"
                            />
                        </div>
                        <div class="flex flex-col">
                            <Dropdown
                                v-model="settings.difficulty"
                                :options="page.props.difficulties"
                                placeholder="Difficulty"
                            />
                        </div>
                        <div class="flex flex-col">
                            <Dropdown
                                v-model="settings.count"
                                :options="page.props.counts"
                                placeholder="Number of Questions"
                            />
                        </div>
                    </div>
                    <div class="mx-auto mt-8">
                        <Button label="Generate Quiz" @click="generate" />
                    </div>
                </form>
            </div>
            <div v-else class="scroll-container w-full overflow-y-auto py-4">
                <div
                    class="mx-auto mb-20 w-[75%]"
                    v-for="(data, index) in quizData"
                    :key="data.id"
                >
                    <div class="mb-4">Question {{ index + 1 }}</div>
                    <div class="text-2xl font-bold">
                        {{ data.question }}
                    </div>

                    <div
                        v-for="answer in data.answers"
                        @click="submitAnswer(data.id)"
                        class="my-4 flex w-full cursor-pointer items-center justify-between rounded border border-[#525050] p-4 text-left"
                        :class="{
                            'bg-emerald-300/60':
                                data.answered && answer.correct,
                        }"
                    >
                        {{ answer.answer }}
                    </div>

                    <div
                        v-if="data.answered"
                        class="mt-2 rounded bg-stone-700/70 p-4 dark:bg-stone-700/50"
                    >
                        <h3 class="text-sm font-bold text-emerald-300/80">
                            Explanation
                        </h3>
                        <p class="mt-2 text-sm font-light">
                            {{ data.description }}
                        </p>
                    </div>
                </div>
            </div>
            <div v-if="quizData" class="mx-auto p-5">
                <Button
                    label="Generate Another Quiz"
                    @click="generateAnotherQuiz"
                />
            </div>
        </Main>
    </AuthenticatedLayout>
</template>
