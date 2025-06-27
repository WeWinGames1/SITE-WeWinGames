<script setup lang="ts">
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader';
import { onMounted, ref } from 'vue';

const props = defineProps({
    color: {
        type: String,
        default: '#FFD700', // Default to gold
    },
});

const container = ref(null);

const init3D = () => {
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, container.value.offsetWidth / container.value.offsetHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true }); // Enable transparency
    renderer.setSize(container.value.offsetWidth, container.value.offsetHeight);
    renderer.outputEncoding = THREE.sRGBEncoding; // Enable gamma correction
    container.value.appendChild(renderer.domElement);

    // Add lighting
    const ambientLight = new THREE.AmbientLight(0xffffff, 1.5); // Increased ambient light intensity
    scene.add(ambientLight);

    const directionalLight = new THREE.DirectionalLight(0xffffff, 2); // Increased directional light intensity
    directionalLight.position.set(5, 10, 7.5); // Position the light
    scene.add(directionalLight);

    let model; // Reference to the loaded model

    // Load the 3D model
    const loader = new GLTFLoader();
    loader.load('/golden_trophy_gltf/golden_trophy.glb', (gltf) => {
        model = gltf.scene;

        // Traverse the model to apply custom color or ensure textures are applied
        model.traverse((child) => {
            if (child.isMesh) {
                // If the model already has a material, modify its color
                if (child.material) {
                    child.material.color = new THREE.Color(props.color);
                }
            }
        });

        scene.add(model);
        model.position.set(0, -1, 0); // Adjust position if needed
    });

    camera.position.z = 5;

    // Animation loop
    const animate = () => {
        requestAnimationFrame(animate);

        // Rotate the model if it has been loaded
        if (model) {
            model.rotation.y += 0.01; // Rotate 360 degrees over time
        }

        renderer.render(scene, camera);
    };

    animate();
};

onMounted(() => {
    init3D();
});
</script>

<template>
    <div ref="container" class="w-full h-40"></div>
</template>

<style scoped>
/* Ensure the container has a fixed size */
div {
    width: 100%;
    height: 100%;
}
</style>