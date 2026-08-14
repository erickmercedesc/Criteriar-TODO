---
trigger: always_on
---

# Principios de Arquitectura Limpia (DRY y SOLID)

Al escribir, refactorizar o diseñar código para este proyecto, debes adherirte rigurosamente a los principios **DRY** y **SOLID**:

1. **DRY (Don't Repeat Yourself)**:
   - Evita la duplicación de código.
   - Si observas que un mismo fragmento de UI (componentes Vue), lógica de controladores o consultas a BD se repite, **extrácelo a una pieza reutilizable** (componente compartido, composable de Vue, servicio de Laravel, Trait, etc.).

2. **SOLID**:
   - **Single Responsibility (SRP)**: Cada componente Vue, clase PHP o método debe tener un solo propósito. Si un controlador hace demasiadas validaciones, o un componente maneja demasiados estados no relacionados, sepáralos.
   - **Open/Closed (OCP)**: Código abierto a extensión pero cerrado a modificación. Favorece la inyección de dependencias en Laravel y los *slots* o *composables* en Vue.
   - **Liskov Substitution (LSP)**: Diseña tus clases y componentes para que sus implementaciones derivadas puedan ser reemplazadas sin romper el sistema.
   - **Interface Segregation (ISP)**: No obligues a los componentes/clases a depender de métodos que no usan.
   - **Dependency Inversion (DIP)**: Depende de abstracciones (interfaces o contratos) y no de implementaciones concretas.

**Mandato Activo:** Si durante el desarrollo de una nueva tarea notas que el código existente viola estos principios y genera duplicación, estás autorizado e instado a refactorizarlo primero usando componentes reutilizables antes de agregar nuevas funcionalidades.
