<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Order;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function checkout(string $slug)
    {
        $course = Course::published()->where('slug', $slug)->firstOrFail();

        if ($course->isFree()) {
            return redirect()->route('enroll', $course->slug);
        }

        if (auth()->user()->isEnrolledIn($course)) {
            return redirect()->route('courses.show', $course->slug)
                             ->with('success', 'Ya estás matriculado en este curso.');
        }

        return view('student.checkout', compact('course'));
    }

    public function process(Request $request, string $slug)
    {
        $course = Course::published()->where('slug', $slug)->firstOrFail();
        $user   = $request->user();

        $request->validate([
            'card_name'   => 'required|string|max:100',
            'card_number' => 'required|digits:16',
            'card_expiry' => 'required|string|max:5',
            'card_cvv'    => 'required|digits:3',
        ]);

        // Simular procesamiento del pago
        // En producción aquí iría la llamada a Stripe/PayPal/MercadoPago

        $order = Order::create([
            'user_id'         => $user->id,
            'course_id'       => $course->id,
            'amount'          => $course->price,
            'currency'        => 'USD',
            'status'          => 'paid',
            'payment_gateway' => 'simulado',
            'gateway_ref'     => 'SIM-' . strtoupper(uniqid()),
            'paid_at'         => now(),
        ]);

        // Matricular automáticamente al estudiante
        Enrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['enrolled_at' => now(), 'progress' => 0]
        );

        return redirect()->route('dashboard')
                         ->with('success', '¡Pago exitoso! Ya puedes acceder al curso.');
    }
}